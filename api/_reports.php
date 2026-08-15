<?php
/**
 * BlaineSide UCP — staff reports.
 *
 * A report about the conduct of a member of staff. It looks like a ban
 * appeal from a distance and is built quite differently, for one reason:
 * an appeal is about a punishment the UCP already holds, and a report is
 * about a person and an incident the UCP knows nothing about. Everything a
 * handler needs is on the report itself.
 *
 * Three rules run through this whole file, and every one of them is checked
 * server-side rather than trusted to the page:
 *
 *   1. WHO MAY OPEN THE PANEL — Management, Founder, or anyone holding the
 *      Staff Management sub-group. That rule already lived in _queues.php
 *      before any of this was built; this file asks it rather than
 *      restating it, so there is one answer and not two.
 *
 *   2. A STAFF MANAGEMENT HOLDER DOES NOT READ A REPORT ABOUT THEMSELVES.
 *      Not in the queue, not in the counts, not by typing the id into the
 *      URL, not by being allocated it. They hold the panel because of a
 *      sub-group, and a sub-group is not a reason to be the one person who
 *      can read the complaint against them.
 *
 *      Management and Founders ARE the exception, deliberately. They sit
 *      above the queue rather than inside it: there is nobody to escalate a
 *      report about a Founder to, so a rule that hid it from them would
 *      leave that report unreadable by anybody. Whether they should HANDLE
 *      one about themselves is a judgement — the page says so plainly on
 *      the report — but it is theirs to make, not a door the UCP bolts.
 *
 *   3. THE REPORTER IS TOLD WHAT WAS DECIDED, NEVER WHO DECIDED IT. They
 *      see the outcome and the closing comment. The handler's name, the
 *      staff-only thread and the running log stay on the staff side.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_teams.php';
require_once __DIR__ . '/_queues.php';

/** Lengths. Long enough for a real account, short enough to stay readable. */
const BS_REPORT_TITLE_MIN    = 8;
const BS_REPORT_TITLE_MAX    = 140;
const BS_REPORT_BODY_MIN     = 60;
const BS_REPORT_BODY_MAX     = 8000;
const BS_REPORT_WANT_MAX     = 2000;
const BS_REPORT_COMMENT_MAX  = 4000;
const BS_REPORT_EVIDENCE_MAX = 12;
const BS_REPORT_STAFF_MAX    = 6;

/** Repeat views by one person inside this window collapse into one log line. */
const BS_REPORT_VIEW_COLLAPSE = 3600;

/** How many reports one account may have open at once. */
const BS_REPORT_OPEN_MAX = 3;


/* =====================================================================
   VOCABULARY

   Every list the page offers comes from here, so the form, the queue and
   the validation cannot drift apart. Adding a channel is one entry.
   ===================================================================== */

/** Where the incident happened. */
function report_channels(): array
{
    return [
        'game'    => 'In-game',
        'forums'  => 'Forums',
        'discord' => 'Discord',
        'other'   => 'Somewhere else',
    ];
}

/** One incident, or a pattern. */
function report_frequencies(): array
{
    return ['once' => 'A one-off incident', 'continuous' => 'Continuous / ongoing'];
}

/**
 * The triage Staff Management does in the first 24-48 hours.
 *
 * These are the four categories the information panel promises, in the
 * same words, because a player who read that panel and then sees a fifth
 * name on their own report has been told two different things.
 */
function report_categories(): array
{
    return [
        'misconduct' => [
            'label' => 'Misconduct Report',
            'blurb' => 'Conduct not up to the standards of the server, a broken rule, '
                     . 'a broken staff policy.',
        ],
        'punishment_appeal' => [
            'label' => 'Punishment Appeal',
            'blurb' => 'A punishment being appealed to be reduced or removed, rather than '
                     . 'conduct being reported.',
        ],
        'subteam' => [
            'label' => 'Subteam Report',
            'blurb' => 'Only about duties performed inside a sub-team. Handed to the '
                     . 'sub-team leader or the line manager.',
        ],
        'rejected' => [
            'label' => 'Rejected Report',
            'blurb' => 'Not reviewed — outside the guidelines, spam, backseat moderation, '
                     . 'or filed on somebody else\'s behalf.',
        ],
    ];
}

/** What was actually done about it. */
function report_outcomes(): array
{
    return [
        'action'    => 'Action taken',
        'no_action' => 'No action taken',
        'referred'  => 'Referred on',
        'rejected'  => 'Rejected',
    ];
}

function report_channel_label(string $k): string   { return report_channels()[$k] ?? $k; }
function report_frequency_label(string $k): string { return report_frequencies()[$k] ?? $k; }
function report_outcome_label(?string $k): ?string  {
    return $k === null || $k === '' ? null : (report_outcomes()[$k] ?? $k);
}
function report_category_label(?string $k): ?string {
    if ($k === null || $k === '') return null;
    $c = report_categories();
    return isset($c[$k]) ? $c[$k]['label'] : $k;
}


/* =====================================================================
   AVAILABILITY

   Everything degrades to "not switched on" when docs/migration-reports.sql
   hasn't been run, the same approach _sessions.php and _teams.php take. A
   UCP one migration behind should be missing a feature, not broken.
   ===================================================================== */

function reports_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_reports LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

function reports_missing_reason(): string
{
    return 'Staff reports aren\'t switched on yet. The report tables have not been migrated.';
}


/* =====================================================================
   WHO MAY DO WHAT
   ===================================================================== */

/**
 * May this account open the Staff Report Panel?
 *
 * Asked of _queues.php rather than answered here. That file already held
 * the rule — Management and Founder, or anyone holding Staff Management —
 * and it is what the sidebar and the stub page have been reading all along.
 */
function report_may_panel(PDO $pdo, array $acc): bool
{
    $q = queues_registry()['reports']['views']['panel'];
    return queue_may_view($q, (int)($acc['admin_rank'] ?? 0), teams_for($pdo, (int)$acc['id']));
}

/** Why they can't, in a sentence written to be shown to them. */
function report_panel_reason(): string
{
    $q = queues_registry()['reports']['views']['panel'];
    return queue_block_reason($q, 'The Staff Report Panel');
}

/**
 * Everyone this report is about, as account ids.
 *
 * The one query rule 2 depends on, so it is its own function and every
 * visibility check goes through it rather than re-deriving the list.
 */
function report_subject_ids(PDO $pdo, int $reportId): array
{
    try {
        $st = $pdo->prepare('SELECT account_id FROM ucp_report_staff WHERE report_id = ?');
        $st->execute([$reportId]);
        return array_map('intval', array_column($st->fetchAll(), 'account_id'));
    } catch (Throwable $e) {
        return [];
    }
}

/** Is this account one of the people the report is about? */
function report_is_subject(PDO $pdo, int $reportId, int $accountId): bool
{
    return in_array($accountId, report_subject_ids($pdo, $reportId), true);
}

/**
 * Is this account hidden from reports that name it?
 *
 * True for a Staff Management holder, false for Management and Founders —
 * see rule 2 at the top of this file. Asked as one function because four
 * different places need the same answer, and a rule copied four times is a
 * rule that will be four different rules by the end of the year.
 */
function report_subject_blind(PDO $pdo, array $acc): bool
{
    if ((int)($acc['admin_rank'] ?? 0) >= BS_QUEUE_REPORT_RANK) return false;
    return report_may_panel($pdo, $acc);
}

/**
 * May this account open this report at all?
 *
 * Returns null when they may, or the sentence to show them when they may
 * not. A string rather than a bool because the reasons are not
 * interchangeable — "this isn't yours" and "you are the subject of it"
 * want different words.
 */
function report_view_block(PDO $pdo, array $acc, array $r): ?string
{
    $me = (int)$acc['id'];

    /* The subject check runs FIRST, before the panel check, so that a
       Staff Management holder reported by somebody is refused for the
       right reason. */
    if (report_subject_blind($pdo, $acc) && report_is_subject($pdo, (int)$r['id'], $me)) {
        return 'This report names you. The Staff Report Panel is held through a sub-group, and '
             . 'a sub-group is not a reason to be the one person who can read the complaint '
             . 'against you. Management will handle it.';
    }

    if ((int)$r['account_id'] === $me) return null;          // their own report
    if (report_may_panel($pdo, $acc))  return null;

    return 'That report is not yours.';
}

/** Is this the reporter's own report? */
function report_is_mine(array $acc, array $r): bool
{
    return (int)$r['account_id'] === (int)$acc['id'];
}


/* =====================================================================
   THE STAFF ROSTER

   Two lists, and they are not the same list.

     report_staff_options()  — who can be REPORTED. Everyone at Support
                               Staff and above, including Management and
                               Founders, minus the person asking. Nobody is
                               un-reportable; that is the point of the queue.

     report_handlers()       — who a report can be ALLOCATED to. Only people
                               who can open the panel, so a report is never
                               handed to somebody who will be refused at the
                               door.
   ===================================================================== */

/**
 * Everyone who can be named in a report, grouped by their group.
 *
 * Grouped rather than a flat alphabetical list because the reporter often
 * knows the rank and not the spelling — "one of the Support team" narrows
 * eighty names to six.
 */
function report_staff_options(PDO $pdo, int $exceptId = 0): array
{
    $st = $pdo->query(
        'SELECT id, username, admin_rank FROM ucp_accounts
          WHERE admin_rank >= 1 AND status = \'active\'
          ORDER BY admin_rank DESC, username ASC'
    );
    $out = [];
    foreach ($st->fetchAll() as $r) {
        if ((int)$r['id'] === $exceptId) continue;
        $out[] = [
            'id'    => (int)$r['id'],
            'name'  => (string)$r['username'],
            'rank'  => (int)$r['admin_rank'],
            'group' => rank_name((int)$r['admin_rank']),
        ];
    }
    return $out;
}

/**
 * Everyone a report can be allocated to.
 *
 * Built by asking report_may_panel() of each candidate rather than by
 * repeating its rank test in SQL — a Staff Management holder at Admin Lvl 1
 * belongs in this list and a WHERE admin_rank >= 8 would silently drop them.
 * The candidate set is small (staff only), so the cost is a handful of rows.
 */
function report_handlers(PDO $pdo): array
{
    $st = $pdo->query(
        'SELECT id, username, admin_rank FROM ucp_accounts
          WHERE admin_rank >= 1 AND status = \'active\'
          ORDER BY admin_rank DESC, username ASC'
    );
    $out = [];
    foreach ($st->fetchAll() as $r) {
        if (!report_may_panel($pdo, ['id' => (int)$r['id'], 'admin_rank' => (int)$r['admin_rank']])) {
            continue;
        }
        $out[] = [
            'id'   => (int)$r['id'],
            'name' => (string)$r['username'],
            'rank' => (int)$r['admin_rank'],
            'role' => rank_name((int)$r['admin_rank']),
        ];
    }
    return $out;
}


/* =====================================================================
   READING ONE REPORT
   ===================================================================== */

/** The staff this report is about, for output. */
function report_subjects(PDO $pdo, int $reportId): array
{
    try {
        $st = $pdo->prepare(
            'SELECT s.account_id, s.name, a.admin_rank, a.status
               FROM ucp_report_staff s
               LEFT JOIN ucp_accounts a ON a.id = s.account_id
              WHERE s.report_id = ?
              ORDER BY a.admin_rank DESC, s.name ASC'
        );
        $st->execute([$reportId]);
        return array_map(function ($r) {
            $rank = $r['admin_rank'] !== null ? (int)$r['admin_rank'] : null;
            return [
                'id'    => (int)$r['account_id'],
                'name'  => (string)$r['name'],
                'rank'  => $rank ?? 0,
                // Null when the account is gone. The report is still a
                // record of what happened; the page says "no longer here"
                // rather than dropping the name it was written about.
                'role'  => $rank === null ? null : rank_name($rank),
                'gone'  => $rank === null,
            ];
        }, $st->fetchAll());
    } catch (Throwable $e) {
        return [];
    }
}

function report_evidence(PDO $pdo, int $reportId): array
{
    $st = $pdo->prepare('SELECT * FROM ucp_report_evidence WHERE report_id = ? ORDER BY id ASC');
    $st->execute([$reportId]);
    return array_map(function ($e) {
        return ['id' => (int)$e['id'], 'url' => (string)$e['url'],
                'note' => $e['note'] !== null && $e['note'] !== '' ? (string)$e['note'] : null];
    }, $st->fetchAll());
}

/**
 * The thread.
 *
 * Two comments are marked rather than stored differently: the one written
 * when the report was allocated, and the one written with the verdict. Both
 * are identified by matching the timestamp on the report row, exactly as
 * _appeals.php identifies a verdict comment — the row already holds the
 * fact, and a `kind` column would be a second copy of it plus a migration.
 *
 * They matter because they read differently from an ordinary reply. "We are
 * looking at this" in the middle of a thread is a remark; the same words
 * marked as the opening comment are the acknowledgement the reporter has
 * been waiting for.
 */
function report_comments(PDO $pdo, int $reportId, bool $staff, ?array $r = null): array
{
    $sql = 'SELECT * FROM ucp_report_comments WHERE report_id = ?'
         . ($staff ? '' : ' AND staff_only = 0')
         . ' ORDER BY created_at ASC, id ASC';
    $st = $pdo->prepare($sql);
    $st->execute([$reportId]);

    $aAt = $r && $r['allocated_at'] !== null ? (int)$r['allocated_at'] : null;
    $cAt = $r && $r['concluded_at'] !== null ? (int)$r['concluded_at'] : null;
    $cBy = $r && $r['concluded_by'] !== null ? (int)$r['concluded_by'] : null;

    return array_map(function ($c) use ($aAt, $cAt, $cBy) {
        $at = (int)$c['created_at'];
        $by = $c['author_id'] !== null ? (int)$c['author_id'] : null;
        $mark = null;
        if ($cAt !== null && $at === $cAt && $by === $cBy) $mark = 'verdict';
        elseif ($aAt !== null && $at === $aAt)             $mark = 'opening';
        return [
            'id'         => (int)$c['id'],
            'author'     => (string)$c['author_name'],
            'staff'      => !empty($c['author_is_staff']),
            'staff_only' => !empty($c['staff_only']),
            'body'       => (string)$c['body'],
            'at'         => $at,
            'mark'       => $mark,
        ];
    }, $st->fetchAll());
}

/** The running log. Staff only — callers must check before asking. */
function report_log(PDO $pdo, int $reportId, int $limit = 200): array
{
    $st = $pdo->prepare(
        'SELECT * FROM ucp_report_log WHERE report_id = ?
          ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit
    );
    $st->execute([$reportId]);
    return array_map(function ($l) {
        return ['actor' => (string)$l['actor_name'], 'action' => (string)$l['action'],
                'detail' => $l['detail'], 'at' => (int)$l['created_at']];
    }, $st->fetchAll());
}

/** Write a line to the running log. Repeat views inside an hour collapse. */
function report_log_add(PDO $pdo, int $reportId, array $actor, string $action,
                        ?string $detail = null): void
{
    /* Both view actions collapse — 'viewed' and 'viewed_named'. Without
       this the log of a long-running report is one handler's refreshes and
       the actual decisions are ten pages down. */
    if ($action === 'viewed' || $action === 'viewed_named') {
        $st = $pdo->prepare(
            'SELECT id FROM ucp_report_log
              WHERE report_id = ? AND actor_id = ? AND action = ? AND created_at > ?
              LIMIT 1'
        );
        $st->execute([$reportId, (int)$actor['id'], $action,
                      time() - BS_REPORT_VIEW_COLLAPSE]);
        if ($st->fetch()) return;
    }

    $pdo->prepare(
        'INSERT INTO ucp_report_log (report_id, actor_id, actor_name, action, detail, created_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$reportId, (int)$actor['id'], (string)$actor['username'],
                $action, $detail, time()]);
}

/** Everything else this person has reported. Their own index. */
function report_history(PDO $pdo, int $accountId, int $exceptId = 0): array
{
    $st = $pdo->prepare(
        'SELECT id, title, status, category, outcome, created_at, concluded_at
           FROM ucp_reports WHERE account_id = ? AND id <> ?
          ORDER BY created_at DESC LIMIT 40'
    );
    $st->execute([$accountId, $exceptId]);
    return array_map(function ($r) {
        return [
            'id'       => (int)$r['id'],
            'title'    => (string)$r['title'],
            'status'   => (string)$r['status'],
            'category' => report_category_label($r['category']),
            'outcome'  => report_outcome_label($r['outcome']),
            'created_at'   => (int)$r['created_at'],
            'concluded_at' => $r['concluded_at'] !== null ? (int)$r['concluded_at'] : null,
        ];
    }, $st->fetchAll());
}

/**
 * One report, shaped for the page.
 *
 * `$staff` here means "is looking at this from the panel", not "is a member
 * of staff" — a Support Staff member reading their own report is not staff
 * for the purposes of this function, and that is the distinction that keeps
 * the handler's name off their copy.
 */
function report_out(PDO $pdo, array $r, array $acc): array
{
    $mine  = report_is_mine($acc, $r);
    $staff = report_may_panel($pdo, $acc);
    $id    = (int)$r['id'];

    $out = [
        'id'         => $id,
        'mine'       => $mine,
        'title'      => (string)$r['title'],
        'channel'    => (string)$r['channel'],
        'channel_label' => report_channel_label((string)$r['channel']),
        'frequency'  => (string)$r['frequency'],
        'frequency_label' => report_frequency_label((string)$r['frequency']),
        'incident_at'=> $r['incident_at'] !== null ? (int)$r['incident_at'] : null,
        'witnesses'  => $r['witnesses'] !== null && $r['witnesses'] !== ''
                        ? (string)$r['witnesses'] : null,
        'body'       => (string)$r['body'],
        'outcome_wanted' => $r['outcome_wanted'] !== null && $r['outcome_wanted'] !== ''
                        ? (string)$r['outcome_wanted'] : null,
        'status'     => (string)$r['status'],
        'category'   => $r['category'] !== null ? (string)$r['category'] : null,
        'category_label' => report_category_label($r['category']),
        'outcome'    => $r['outcome'] !== null ? (string)$r['outcome'] : null,
        'outcome_label'  => report_outcome_label($r['outcome']),
        'subjects'   => report_subjects($pdo, $id),
        'evidence'   => report_evidence($pdo, $id),
        'comments'   => report_comments($pdo, $id, $staff, $r),
        'comments_enabled' => !empty($r['comments_enabled']),
        'created_at' => (int)$r['created_at'],
        'updated_at' => (int)$r['updated_at'],
        'allocated_at'  => $r['allocated_at'] !== null ? (int)$r['allocated_at'] : null,
        'concluded_at'  => $r['concluded_at'] !== null ? (int)$r['concluded_at'] : null,

        /* Rule 3. The reporter is told what was decided, never who decided
           it or who was handling it. Both names are staff-side only. */
        'handler'      => $staff ? ($r['handler_name'] ?: null) : null,
        'concluded_by' => $staff ? ($r['concluded_by_name'] ?: null) : null,

        'viewer' => [
            'staff' => $staff,
            'mine'  => $mine,
            /* Reachable only by Management and Founders — everybody else
               who could be named is refused at the door. The page draws a
               warning rather than a lock: the judgement is theirs, and the
               UCP's job is to make sure it is a judgement they know they
               are making. */
            'named' => $staff && report_is_subject($pdo, $id, (int)$acc['id']),
        ],
    ];

    /* Who sent it, and their history of sending them. Staff only: a report
       is judged partly on whether this is the first one this person has
       filed or the ninth. */
    if ($staff) {
        $u = $pdo->prepare('SELECT id, username, admin_rank, status, created_at
                              FROM ucp_accounts WHERE id = ? LIMIT 1');
        $u->execute([(int)$r['account_id']]);
        $row = $u->fetch();

        $out['reporter'] = $row ? [
            'id'      => (int)$row['id'],
            'name'    => (string)$row['username'],
            'rank'    => (int)$row['admin_rank'],
            'role'    => rank_name((int)$row['admin_rank']),
            'status'  => (string)$row['status'],
            'created_at' => strtotime((string)$row['created_at']) ?: null,
        ] : null;

        $out['history'] = report_history($pdo, (int)$r['account_id'], $id);
        $out['log']     = report_log($pdo, $id);
        $out['handlers']= report_handlers($pdo);
        $out['categories'] = array_map(function ($k, $c) {
            return ['key' => $k, 'label' => $c['label'], 'blurb' => $c['blurb']];
        }, array_keys(report_categories()), array_values(report_categories()));
        $out['outcomes'] = array_map(function ($k, $l) {
            return ['key' => $k, 'label' => $l];
        }, array_keys(report_outcomes()), array_values(report_outcomes()));
    } else {
        $out['reporter'] = null;
        $out['history']  = report_history($pdo, (int)$r['account_id'], $id);
    }

    return $out;
}

/**
 * How many reports this person has open, and whether they may send another.
 *
 * A cap rather than a cooldown. A cooldown punishes somebody with two
 * genuine reports in one week; a cap stops the queue being filled by one
 * person and lifts itself the moment their existing reports are answered.
 */
function report_eligibility(PDO $pdo, array $acc): array
{
    if (!reports_available($pdo)) {
        return ['may' => false, 'why' => reports_missing_reason(), 'open' => 0];
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM ucp_reports
                          WHERE account_id = ? AND status = \'pending\'');
    $st->execute([(int)$acc['id']]);
    $open = (int)$st->fetchColumn();

    if ($open >= BS_REPORT_OPEN_MAX) {
        return [
            'may'  => false,
            'open' => $open,
            'why'  => 'You already have ' . $open . ' staff reports open. Wait for one to be '
                    . 'concluded before sending another — adding a fourth does not make the '
                    . 'first three move faster.',
        ];
    }
    return ['may' => true, 'why' => null, 'open' => $open];
}
