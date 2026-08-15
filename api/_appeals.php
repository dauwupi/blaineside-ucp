<?php
/**
 * BlaineSide UCP — ban appeals.
 *
 * The rules, in one place, because they are the whole feature. An appeal
 * form is easy; deciding who may open which appeal, what may be said in
 * front of the appellant, and when somebody may appeal again is the part
 * that has to be right.
 *
 * The rules:
 *
 *   - You can only appeal something that is IN FORCE and marked
 *     appealable. Kicks and warnings are not punishments in this system
 *     at all (see _punish.php), so there is nothing to exclude.
 *   - One open appeal at a time. Not one per punishment — one, full stop.
 *     Somebody with a game ban and a Discord ban writes one appeal and
 *     ticks two boxes.
 *   - A rejected appeal cannot be re-submitted for three months.
 *   - An accepted appeal ends the matter. If the punishment is still in
 *     force after that, it is a new punishment and a new appeal.
 *
 * And the two that matter most:
 *
 *   - A staff-only comment is never returned to the appellant. Not hidden
 *     by the page — absent from the response.
 *   - The running log is staff-only for the same reason, and by the same
 *     mechanism.
 */

require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_punish.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_teams.php';

/** Support Staff and above work the appeal queue — see api/_queues.php. */
const BS_APPEAL_STAFF_RANK = 1;

/**
 * Senior Admin and above.
 *
 * Two things sit here rather than with the wider staff team: reassigning an
 * appeal, and speaking on one you are not handling. Both are ways to reach
 * over the head of whoever is dealing with it, and an appeal that three
 * people are answering at once is worse for the appellant than a slow one.
 */
const BS_APPEAL_MANAGE_RANK = 6;

/**
 * How long a rejected appellant waits, in days.
 *
 * The rejecting administrator picks one. A fixed period was the wrong shape:
 * an appeal denied because it was three lines long deserves a few days, and
 * one denied for the fourth time on the same permanent ban does not deserve
 * the same. Six rungs, and the page suggests the next one up from whatever
 * they got last time.
 *
 * The list is closed and checked server-side — a wait is a consequence, and
 * a consequence somebody can type a number into is not a rule.
 */
const BS_APPEAL_WAITS = [3, 7, 14, 21, 30, 60];

/** Used only to backfill appeals decided before waits existed. */
const BS_APPEAL_COOLDOWN = 7776000;      // 90 days

/** Repeat views by one person inside this window are one log line. */
const BS_APPEAL_VIEW_COLLAPSE = 3600;

const BS_APPEAL_BODY_MIN   = 40;
const BS_APPEAL_BODY_MAX   = 8000;
const BS_APPEAL_COMMENT_MAX = 4000;
const BS_APPEAL_EVIDENCE_MAX = 8;

/** Do the appeal tables exist? False until migration-appeals.sql runs. */
function appeals_available(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try { $pdo->query('SELECT 1 FROM ucp_appeals LIMIT 1'); $ok = true; }
    catch (Throwable $e) { $ok = false; }
    return $ok;
}

/**
 * Has docs/migration-appeal-cooldown.sql been run?
 *
 * Four columns arrived after the appeal system did: the wait a rejection
 * sets, and who overturned it. Every read and write of them is guarded by
 * this, so a server one migration behind loses those two features and keeps
 * everything else — rather than answering "Something went wrong" on the one
 * page a banned player can still reach.
 */
function appeals_has_waits(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try { $pdo->query('SELECT reappeal_at, overruled_at FROM ucp_appeals LIMIT 1'); $ok = true; }
    catch (Throwable $e) { $ok = false; }
    return $ok;
}

function appeals_missing_reason(): string
{
    return 'Ban appeals aren\'t set up on this server yet — docs/migration-appeals.sql '
         . 'hasn\'t been run.';
}

/** The wait ladder, as the panel draws it. */
function appeal_wait_options(): array
{
    $out = [];
    foreach (BS_APPEAL_WAITS as $d) {
        $out[] = ['days' => $d, 'label' => $d . ' days'];
    }
    return $out;
}

/**
 * The rung this account has earned.
 *
 * One rejection puts them on 3 days, two on 7, and so on to 60 where it
 * stops. Only a suggestion — the administrator can pick any rung, because
 * the ladder cannot know that this particular appeal was written in good
 * faith and simply wrong.
 */
function appeal_suggested_wait(PDO $pdo, int $accountId): int
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM ucp_appeals WHERE account_id = ? AND status = \'rejected\''
    );
    $st->execute([$accountId]);
    $n = (int)$st->fetchColumn();                    // rejections BEFORE this one
    $i = min($n, count(BS_APPEAL_WAITS) - 1);
    return BS_APPEAL_WAITS[$i];
}

/**
 * May this account overrule a rejected appeal?
 *
 * Staff Management, Management and the Founder. The same people who can see
 * staff accounts in Administrative Search, and for the same reason: this is
 * the power to overturn another administrator's decision, and it belongs
 * with the people whose job is the staff team rather than with rank alone.
 */
function appeal_may_overrule(PDO $pdo, array $acc): bool
{
    if ((int)($acc['admin_rank'] ?? 0) >= 8) return true;
    return function_exists('has_team') && has_team($pdo, (int)$acc['id'], 'staff_management');
}

/** Support Staff and above. */
function appeal_is_staff(array $acc): bool
{
    return (int)($acc['admin_rank'] ?? 0) >= BS_APPEAL_STAFF_RANK;
}

/**
 * The rank from which an appeal of one's own is still seen — and handled —
 * as staff.
 *
 * Below it, a member of staff appealing their own punishment is just an
 * appellant: they get the player's view, and somebody else decides. That is
 * right for almost everybody.
 *
 * Management and Founders are the exception, for the same reason they are
 * the exception on a staff report: there is no queue above them. Hiding
 * their own appeal from them would not make anybody more impartial, it would
 * only mean the most senior people in the community cannot see a page
 * everyone else can. The page marks the conflict plainly and the log carries
 * their name; the judgement is theirs.
 */
const BS_APPEAL_SELF_RANK = 8;      // Management

function appeal_sees_own_as_staff(array $acc): bool
{
    return (int)($acc['admin_rank'] ?? 0) >= BS_APPEAL_SELF_RANK;
}

/**
 * May this account read this appeal?
 *
 * Its author always may. Staff may. Nobody else — an appeal is about one
 * person's punishment and is nobody else's business.
 */
function appeal_may_view(array $acc, array $appeal): bool
{
    if ((int)$acc['id'] === (int)$appeal['account_id']) return true;
    return appeal_is_staff($acc);
}

/**
 * May this account CONCLUDE this appeal?
 *
 * Not the person who issued the punishment, and not the appellant. The
 * first is the rule the page states out loud: whoever banned you does not
 * get to decide whether the ban stands.
 */
function appeal_conclude_block(PDO $pdo, array $acc, array $appeal, ?array $punishment): ?string
{
    if (!appeal_is_staff($acc)) {
        return 'Concluding an appeal is for ' . rank_name(BS_APPEAL_STAFF_RANK) . ' and above.';
    }
    if ((int)$acc['id'] === (int)$appeal['account_id'] && !appeal_sees_own_as_staff($acc)) {
        return 'You can\'t decide your own appeal.';
    }
    if ($appeal['status'] !== 'pending') {
        return 'This appeal was already ' . $appeal['status'] . '.';
    }
    /* The administrator who issued the punishment is NOT barred from deciding
       the appeal against it — they are the default handler, because they are
       the one who knows what happened. If a second opinion is wanted, a
       Senior Admin reassigns it. */
    return appeal_may_act($acc, $appeal)
        ? null
        : 'This appeal is being handled by ' . ($appeal['handler_name'] ?: 'someone else')
        . '. ' . rank_name(BS_APPEAL_MANAGE_RANK) . ' and above can take it over.';
}

/**
 * May this staff member act on this appeal — comment on it, or decide it?
 *
 * The handler, or Senior Admin and above. Not every member of staff who can
 * open it: an appeal is a conversation between one player and one handler,
 * and four people answering at once is worse for the appellant than a slow
 * reply. Anyone else reads it, and takes it up with the handler.
 */
function appeal_may_act(array $acc, array $appeal): bool
{
    if (!appeal_is_staff($acc)) return false;
    if ((int)$acc['id'] === (int)$appeal['account_id']) return false;
    if ((int)($appeal['handler_id'] ?? 0) === (int)$acc['id']) return true;
    return (int)$acc['admin_rank'] >= BS_APPEAL_MANAGE_RANK;
}

/** May they reassign it, or change who may reply? Senior Admin and above. */
function appeal_may_manage(array $acc): bool
{
    return appeal_is_staff($acc) && (int)$acc['admin_rank'] >= BS_APPEAL_MANAGE_RANK;
}

/** Everyone who could be given an appeal: Support Staff and above, active. */
function appeal_handlers(PDO $pdo): array
{
    $st = $pdo->prepare(
        'SELECT id, username, admin_rank FROM ucp_accounts
          WHERE status = \'active\' AND admin_rank >= ?
          ORDER BY admin_rank DESC, username_lower ASC'
    );
    $st->execute([BS_APPEAL_STAFF_RANK]);
    return array_map(function ($r) {
        return ['id' => (int)$r['id'], 'name' => (string)$r['username'],
                'rank' => (int)$r['admin_rank'], 'role' => rank_name((int)$r['admin_rank'])];
    }, $st->fetchAll());
}

/**
 * What can this account appeal right now, and may they?
 *
 * Returns everything the submit page needs to explain itself, including
 * the reasons it is saying no. A page that can only render "you may not"
 * sends people to Discord to ask why.
 */
function appeal_eligibility(PDO $pdo, array $acc): array
{
    $out = [
        'may'        => false,
        'why'        => null,
        'open'       => null,     // id of an appeal already in progress
        'cooldown'   => null,     // unix time they may appeal again
        'punishments'=> [],
    ];

    if (!appeals_available($pdo)) {
        $out['why'] = appeals_missing_reason();
        return $out;
    }

    $active = punish_active_for($pdo, (int)$acc['id']);
    foreach ($active as $p) $out['punishments'][] = punish_out($p);

    // An appeal already in progress. Sent back rather than refused: the
    // page links straight to it, which is what they actually wanted.
    $st = $pdo->prepare(
        'SELECT id FROM ucp_appeals WHERE account_id = ? AND status = \'pending\'
          ORDER BY id DESC LIMIT 1'
    );
    $st->execute([(int)$acc['id']]);
    $open = $st->fetch();
    if ($open) {
        $out['open'] = (int)$open['id'];
        $out['why']  = 'You already have an appeal open. You can only have one at a time.';
        return $out;
    }

    /* No longer refused for having nothing on file.
     *
     * The UCP records in-game punishments and nothing else: a forum ban lives
     * on the forum and a Discord ban lives in Discord, and neither writes a
     * row here. Requiring one meant somebody banned on Discord was told there
     * was nothing to appeal — false, and the opposite of why Discord is on
     * the form.
     *
     * So eligibility is now about the APPELLANT: do they already have one
     * open, and are they inside a wait. WHAT they may appeal is decided per
     * platform on submit, where in-game still needs a punishment on file
     * because that is the only one the UCP can check. */

    /* Rejected, and still inside the wait the rejecting administrator set.
     *
     * reappeal_at is on the appeal rather than computed here, because the
     * wait was a decision somebody made about that appeal — recomputing it
     * from a constant would silently rewrite their decision the next time
     * the constant changed. */
    $st = $pdo->prepare(
        'SELECT concluded_at' . (appeals_has_waits($pdo) ? ', reappeal_at' : '') . '
           FROM ucp_appeals
          WHERE account_id = ? AND status = \'rejected\'
          ORDER BY concluded_at DESC LIMIT 1'
    );
    $st->execute([(int)$acc['id']]);
    $last = $st->fetch();
    if ($last) {
        $until = isset($last['reappeal_at']) && $last['reappeal_at'] !== null
            ? (int)$last['reappeal_at']
            // Decided before waits existed. Falls back to the old 90 days
            // rather than letting those appellants straight back in.
            : ($last['concluded_at'] !== null
                ? (int)$last['concluded_at'] + BS_APPEAL_COOLDOWN : 0);

        if ($until > time()) {
            $out['cooldown'] = $until;
            $out['why'] = 'Your last appeal was denied, and the administrator who decided it set '
                        . 'a wait before you can appeal again.';
            return $out;
        }
    }

    $out['may'] = true;
    return $out;
}

/**
 * Every punishment an appeal is against.
 *
 * An appeal can cover more than one: somebody banned in game AND on the
 * forums writes one appeal and ticks two boxes, and it would be absurd to
 * make them write the same account of the same evening twice and wait for
 * two verdicts on it.
 *
 * ucp_appeal_punishments is the real answer. ucp_appeals.punishment_id is
 * kept as the first of them, because a single id is what the queue and the
 * "who issued this" check want, and because appeals written before the
 * join table existed have nothing else.
 *
 * Falls back to what is in force on the account if the rows are gone, so an
 * appeal whose punishment was deleted still renders rather than 500ing on a
 * staff member mid-decision.
 */
function appeal_punishments(PDO $pdo, array $appeal): array
{
    $out = [];
    try {
        $st = $pdo->prepare(
            'SELECT p.* FROM ucp_appeal_punishments ap
               JOIN ucp_punishments p ON p.id = ap.punishment_id
              WHERE ap.appeal_id = ?
              ORDER BY p.issued_at ASC, p.id ASC'
        );
        $st->execute([(int)$appeal['id']]);
        $out = $st->fetchAll();
    } catch (Throwable $e) {
        // Join table not migrated yet — fall through to the single id.
    }
    if ($out) return $out;

    if ($appeal['punishment_id'] !== null) {
        $p = punish_by_id($pdo, (int)$appeal['punishment_id']);
        if ($p) return [$p];
        // The row it pointed at is gone. Fall back to what is in force so a
        // staff member mid-decision sees something rather than a 500.
        return punish_active_for($pdo, (int)$appeal['account_id']);
    }

    /* Nothing attached, on purpose: a forum or Discord ban the UCP does not
     * record. Returning "whatever is in force" here would staple an unrelated
     * game lock to a Discord appeal and invite somebody to lift it. */
    return [];
}

/** The first of them — what a single-punishment check wants. */
function appeal_punishment(PDO $pdo, array $appeal): ?array
{
    $all = appeal_punishments($pdo, $appeal);
    return $all ? $all[0] : null;
}

/** Did this person issue ANY of the punishments under appeal? */
function appeal_issued_any(array $punishments, int $accountId): bool
{
    foreach ($punishments as $p) {
        if ((int)($p['issued_by'] ?? 0) === $accountId) return true;
    }
    return false;
}

/** Comma-separated platform keys -> a clean, ordered list. */
function appeal_platforms_in($raw): array
{
    $valid = array_keys(punish_platforms());
    $in    = is_array($raw) ? $raw : explode(',', (string)$raw);
    $out   = [];
    foreach ($valid as $v) {                       // registry order, not input order
        foreach ($in as $x) {
            if (strtolower(trim((string)$x)) === $v) { $out[] = $v; break; }
        }
    }
    return $out;
}

/**
 * Comments on an appeal.
 *
 * $staff decides what comes back. For the appellant the staff-only rows
 * are not fetched at all — the filter is in the WHERE clause, so there is
 * no version of this where a front-end mistake shows one.
 */
function appeal_comments(PDO $pdo, int $appealId, bool $staff, ?array $appeal = null): array
{
    $sql = 'SELECT * FROM ucp_appeal_comments WHERE appeal_id = ?'
         . ($staff ? '' : ' AND staff_only = 0')
         . ' ORDER BY created_at ASC, id ASC';
    $st = $pdo->prepare($sql);
    $st->execute([$appealId]);

    /* Which comment IS the verdict, and which IS the overrule.
     *
     * Not a column: both are written in the same request that stamps the
     * appeal, with the same timestamp and the same author, so the appeal row
     * already identifies them exactly. A `kind` column would be a second
     * copy of that fact and a migration to add it.
     *
     * It matters because these two read differently from an ordinary reply.
     * "The ban stands" in the middle of a thread is an opinion; the same
     * words marked as the verdict are the decision. */
    $vAt = $appeal && $appeal['concluded_at'] !== null ? (int)$appeal['concluded_at'] : null;
    $vBy = $appeal && $appeal['concluded_by'] !== null ? (int)$appeal['concluded_by'] : null;
    $oAt = $appeal && isset($appeal['overruled_at']) && $appeal['overruled_at'] !== null
         ? (int)$appeal['overruled_at'] : null;
    $oBy = $appeal && isset($appeal['overruled_by']) && $appeal['overruled_by'] !== null
         ? (int)$appeal['overruled_by'] : null;
    $status = $appeal ? (string)$appeal['status'] : '';

    return array_map(function ($c) use ($vAt, $vBy, $oAt, $oBy, $status) {
        $at  = (int)$c['created_at'];
        $by  = $c['author_id'] !== null ? (int)$c['author_id'] : null;
        $mark = null;
        if ($oAt !== null && $at === $oAt && $by === $oBy) {
            $mark = 'overrule';
        } elseif ($vAt !== null && $at === $vAt && $by === $vBy) {
            // If it was later overturned, the original verdict was a rejection.
            $mark = 'verdict';
        }
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

function appeal_evidence(PDO $pdo, int $appealId): array
{
    $st = $pdo->prepare('SELECT * FROM ucp_appeal_evidence WHERE appeal_id = ? ORDER BY id ASC');
    $st->execute([$appealId]);
    return array_map(function ($e) {
        return ['id' => (int)$e['id'], 'url' => (string)$e['url'],
                'note' => $e['note'] !== null && $e['note'] !== '' ? (string)$e['note'] : null];
    }, $st->fetchAll());
}

/** The running log. Staff only — callers must check before asking. */
function appeal_log(PDO $pdo, int $appealId, int $limit = 200): array
{
    $st = $pdo->prepare(
        'SELECT * FROM ucp_appeal_log WHERE appeal_id = ?
          ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit
    );
    $st->execute([$appealId]);
    return array_map(function ($l) {
        return ['actor' => (string)$l['actor_name'], 'action' => (string)$l['action'],
                'detail' => $l['detail'], 'at' => (int)$l['created_at']];
    }, $st->fetchAll());
}

/**
 * Write a line to the running log.
 *
 * Views collapse: the same person viewing twice inside an hour extends
 * nothing and adds nothing. Without this the log of a long-running appeal
 * is one handler's refreshes and the actual decisions are ten pages down.
 */
function appeal_log_add(PDO $pdo, int $appealId, array $actor, string $action,
                        ?string $detail = null): void
{
    if ($action === 'viewed') {
        $st = $pdo->prepare(
            'SELECT id FROM ucp_appeal_log
              WHERE appeal_id = ? AND actor_id = ? AND action = \'viewed\' AND created_at > ?
              LIMIT 1'
        );
        $st->execute([$appealId, (int)$actor['id'], time() - BS_APPEAL_VIEW_COLLAPSE]);
        if ($st->fetch()) return;
    }

    $pdo->prepare(
        'INSERT INTO ucp_appeal_log (appeal_id, actor_id, actor_name, action, detail, created_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$appealId, (int)$actor['id'], (string)$actor['username'],
                $action, $detail, time()]);
}

/**
 * The forum display name and profile URL for a member id.
 *
 * A member number is not an identity. A handler deciding a forum ban needs
 * the name they will search for and a link they can open, not a number they
 * have to paste into a URL by hand.
 *
 * Cached in the session for ten minutes, and failure is silent: a slow or
 * unreachable forum must not stop an appeal from rendering. The id is always
 * shown, so the page degrades to what it had before rather than to nothing.
 */
function appeal_forum_name(PDO $pdo, int $memberId): array
{
    global $CONFIG;
    $base = rtrim((string)($CONFIG['forum']['url'] ?? 'https://forum.blaineside.com'), '/');
    $out  = ['name' => null, 'url' => null];

    $cache = $_SESSION['appeal_forum_names'][$memberId] ?? null;
    if (is_array($cache) && (int)($cache['at'] ?? 0) > time() - 600) {
        return ['name' => $cache['name'], 'url' => $cache['url']];
    }

    require_once __DIR__ . '/_ips.php';
    $url = function_exists('ips_endpoint') ? ips_endpoint('core/members/' . $memberId) : null;
    if ($url !== null && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERPWD        => ips_userpwd(),
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && is_string($body)) {
            $d = json_decode($body, true);
            if (is_array($d) && !empty($d['name'])) {
                $out['name'] = (string)$d['name'];
                // Friendly URLs are off on this forum, so build the form that
                // works either way rather than trusting profileUrl.
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $out['name']));
                $out['url'] = $base . '/index.php?/profile/' . $memberId . '-'
                            . trim($slug, '-') . '/';
            }
        }
    }
    if ($out['url'] === null) {
        // No name, but the profile is still reachable by id alone.
        $out['url'] = $base . '/index.php?/profile/' . $memberId . '/';
    }

    $_SESSION['appeal_forum_names'][$memberId] =
        ['name' => $out['name'], 'url' => $out['url'], 'at' => time()];
    return $out;
}

/**
 * Every appeal this account has made, newest first.
 *
 * Shown to the appellant so they can read back what they were told last
 * time — a rejection three months ago whose reason they have forgotten is
 * the single most common cause of the identical appeal arriving again. And
 * shown to staff, because "has this person appealed before, and what
 * happened" is the first question a handler asks and the one that used to
 * need a database query to answer.
 *
 * Bodies and comments are not included. This is an index; opening one is a
 * click away and goes through api/appeal.php, which applies the same rules
 * it always does.
 */
function appeal_history(PDO $pdo, int $accountId, int $exceptId = 0, bool $staff = false): array
{
    $waits = appeals_has_waits($pdo);
    $st = $pdo->prepare(
        'SELECT id, status, platforms, created_at, concluded_at, concluded_by_name, handler_name'
        . ($waits ? ', reappeal_at, overruled_at, overruled_by_name' : '') . '
           FROM ucp_appeals
          WHERE account_id = ? AND id <> ?
          ORDER BY created_at DESC, id DESC
          LIMIT 25'
    );
    $st->execute([$accountId, $exceptId]);

    return array_map(function ($a) use ($staff) {
        return [
            'id'         => (int)$a['id'],
            'status'     => (string)$a['status'],
            'platforms'  => appeal_platforms_in($a['platforms']),
            'created_at' => (int)$a['created_at'],
            'concluded_at' => $a['concluded_at'] !== null ? (int)$a['concluded_at'] : null,
            // Who decided it is a staff fact, the same as everywhere else.
            'by'         => $staff ? ($a['concluded_by_name'] ?: null) : null,
            'handler'    => $staff ? ($a['handler_name'] ?: null) : null,
            'overruled'  => !empty($a['overruled_at']),
            'reappeal_at'=> isset($a['reappeal_at']) && $a['reappeal_at'] !== null
                            ? (int)$a['reappeal_at'] : null,
        ];
    }, $st->fetchAll());
}

/**
 * The Previous appeals card on the Administrative Record.
 *
 * appeal_history() above answers "what has this person appealed before" for
 * the appeal page, where the reader already has an appeal in front of them.
 * This answers the same question for the record, where they do not — so each
 * row also carries what the appeal was against and a short line saying what
 * happened, and the row links straight into the appeal.
 */
function appeal_record_list(PDO $pdo, int $accountId, bool $staff = false): array
{
    if (!appeals_available($pdo)) return [];
    $waits = appeals_has_waits($pdo);

    $st = $pdo->prepare(
        'SELECT a.id, a.status, a.created_at, a.concluded_at, a.concluded_by_name,
                a.handler_name, a.platforms'
        . ($waits ? ', a.reappeal_at, a.overruled_at, a.overruled_by_name' : '') . '
           FROM ucp_appeals a
          WHERE a.account_id = ?
          ORDER BY a.created_at DESC, a.id DESC
          LIMIT 100'
    );
    $st->execute([$accountId]);
    $rows = $st->fetchAll();
    if (!$rows) return [];

    /* What each appeal was against, in one pass. An appeal can name more than
       one punishment; the card shows the first and says how many others. */
    $against = [];
    try {
        $ap = $pdo->prepare(
            'SELECT ap.appeal_id, p.kind, p.reason
               FROM ucp_appeal_punishments ap
               JOIN ucp_punishments p ON p.id = ap.punishment_id
               JOIN ucp_appeals a ON a.id = ap.appeal_id
              WHERE a.account_id = ?
              ORDER BY ap.appeal_id, ap.punishment_id'
        );
        $ap->execute([$accountId]);
        foreach ($ap->fetchAll() as $r) {
            $against[(int)$r['appeal_id']][] = [
                'label'  => punish_kinds()[(string)$r['kind']]['label'] ?? (string)$r['kind'],
                'reason' => $r['reason'] !== null && $r['reason'] !== ''
                              ? (string)$r['reason'] : null,
            ];
        }
    } catch (Throwable $e) {
        // The join table is part of the appeals migration; without it the
        // appeals still list, they just do not say what they were against.
    }

    $now = time();
    return array_map(function ($a) use ($staff, $against, $waits, $now) {
        $id  = (int)$a['id'];
        $ps  = $against[$id] ?? [];
        $head = $ps
            ? $ps[0]['label'] . (count($ps) > 1 ? ' and ' . (count($ps) - 1) . ' more' : '')
            : 'Appeal';
        $what = $ps && $ps[0]['reason'] !== null ? $ps[0]['reason'] : null;

        /* One short line for what happened. The status pill already says
           accepted or rejected; this says what that meant. */
        $note = null;
        if ($a['status'] === 'pending') {
            $note = 'Waiting on a verdict';
        } elseif ($waits && !empty($a['overruled_at'])) {
            $note = 'Rejection overturned'
                  . ($staff && $a['overruled_by_name'] ? ' by ' . $a['overruled_by_name'] : '');
        } elseif ($a['status'] === 'rejected' && $waits && !empty($a['reappeal_at'])) {
            $note = (int)$a['reappeal_at'] > $now
                ? 'Can appeal again from ' . gmdate('j M Y', (int)$a['reappeal_at'])
                : 'Free to appeal again';
        } elseif ($a['status'] === 'accepted') {
            $note = 'Punishment lifted';
        }

        return [
            'id'         => $id,
            'status'     => (string)$a['status'],
            'against'    => $head,
            'what'       => $what,
            'at'         => (int)$a['created_at'],
            'handler'    => $staff ? ($a['handler_name'] ?: null) : null,
            'note'       => $note,
        ];
    }, $rows);
}

/** One appeal row, resolved for whoever is looking at it. */
function appeal_out(PDO $pdo, array $a, array $acc): array
{
    $mine = (int)$acc['id'] === (int)$a['account_id'];

    /* Staff ON THIS APPEAL — not staff in general.
     *
     * A Support Staff member can be banned and appeal it like anyone else,
     * and on that appeal they are the appellant. Reading it as "is staff"
     * would hand them the staff-only comments about their own case and the
     * log of who has been looking at it, which is the exact conversation
     * they must not see. Rank is not a reason to see your own file. */
    /* Their own appeal is the player's view — unless they are Management or
       a Founder, who keep the staff view of everything. */
    $staff = appeal_is_staff($acc) && (!$mine || appeal_sees_own_as_staff($acc));

    $owner = null; $accounts = null; $whois = null;
    $st = $pdo->prepare(
        'SELECT id, username, admin_rank, status, created_at, last_login, totp_enabled,
                forum_member_id, discord, discord_username, discord_linked_at
           FROM ucp_accounts WHERE id = ? LIMIT 1'
    );
    $st->execute([(int)$a['account_id']]);
    $row = $st->fetch();
    if ($row) {
        $owner = (string)$row['username'];

        /* An appeal against a forum or Discord ban is about an account the
         * UCP already knows. Making a handler go and look it up separately —
         * on a page they may not have open, for a name the player may have
         * spelled differently — is the sort of small friction that turns a
         * two-minute decision into a ten-minute one. */
        $fname = null; $furl = null;
        if ($row['forum_member_id'] !== null) {
            $f = appeal_forum_name($pdo, (int)$row['forum_member_id']);
            $fname = $f['name']; $furl = $f['url'];
        }
        $accounts = [
            'forum' => [
                'linked'    => $row['forum_member_id'] !== null,
                'member_id' => $row['forum_member_id'] !== null
                               ? (int)$row['forum_member_id'] : null,
                'name'      => $fname,
                'url'       => $furl,
            ],
            'discord' => [
                'linked'    => !empty($row['discord_username']),
                'username'  => $row['discord_username'] ?: null,
                // What they typed at sign-up, which is NOT the same fact.
                'given'     => $row['discord'] ?: null,
                'linked_at' => $row['discord_linked_at'] !== null
                               ? (int)$row['discord_linked_at'] : null,
            ],
        ];

        /* Who this person IS, for the handler.
         *
         * A handler reading an appeal has one question after "what does it
         * say": who am I dealing with. Answering it used to mean opening
         * Administrative Search in another tab, finding the account, and
         * coming back — and on a Discord or forum ban, where the UCP has no
         * punishment on file, this card previously said nothing at all.
         *
         * Staff only, and everything in it is a fact the same person could
         * read on the lookup page a click away. The point is not the access,
         * it is not making them go and get it. */
        if ($staff) {
            $created = strtotime((string)$row['created_at']) ?: null;
            $login   = $row['last_login'] !== null
                        ? (strtotime((string)$row['last_login']) ?: null) : null;

            /* Last used the UCP, not last signed in — a remembered browser
               signs in once and stays signed in for months. */
            $seen = null;
            try {
                if (sessions_available($pdo)) {
                    $q = $pdo->prepare(
                        'SELECT MAX(last_seen) FROM ucp_sessions
                          WHERE account_id = ? AND revoked_at IS NULL'
                    );
                    $q->execute([(int)$row['id']]);
                    $v = $q->fetchColumn();
                    if ($v !== null && $v !== false) $seen = (int)$v;
                }
            } catch (Throwable $e) { /* no sessions table */ }

            /* The record, summarised. Enough to know whether this is a first
               offence or the fifth, without leaving the appeal. */
            $rec = null;
            try {
                $r = record_for($pdo, (int)$row['id'], true, $acc);
                if (!empty($r['available'])) {
                    $rec = [
                        'total'    => (int)$r['summary']['total'],
                        'active'   => (int)$r['summary']['active'],
                        'bans'     => (int)$r['summary']['bans'],
                        'warnings' => (int)$r['summary']['warnings'],
                        'kicks'    => (int)$r['summary']['kicks'],
                        'locks'    => (int)($r['counts']['lock'] ?? 0),
                        'level'    => (string)$r['summary']['level'],
                        'last_at'  => $r['summary']['last_at'],
                    ];
                }
            } catch (Throwable $e) { /* punishments not migrated */ }

            $whois = [
                'id'          => (int)$row['id'],
                'name'        => (string)$row['username'],
                'rank'        => (int)$row['admin_rank'],
                'role'        => rank_name((int)$row['admin_rank']),
                'status'      => (string)$row['status'],
                'created_at'  => $created,
                'member_days' => $created ? (int)floor((time() - $created) / 86400) : null,
                'last_seen'   => $seen,
                'last_login'  => $login,
                'twofa'       => !empty($row['totp_enabled']),
                'record'      => $rec,
            ];
        }
    }

    $ps = appeal_punishments($pdo, $a);

    /* Whether the appellant is told which administrator issued the
       punishment. Off by default, and there is no switch yet — the action
       exists on the page, disabled, so the shape is visible. Staff always
       see it. */
    $showIssuer = $staff;

    $out = [
        'id'       => (int)$a['id'],
        'mine'     => $mine,
        'user'     => $owner,
        'user_id'  => (int)$a['account_id'],
        'platforms'=> appeal_platforms_in($a['platforms']),
        'body'     => (string)$a['body'],
        'status'   => (string)$a['status'],
        'handler'  => $a['handler_name'] ?: null,
        'comments_enabled' => !empty($a['comments_enabled']),
        'created_at' => (int)$a['created_at'],
        'updated_at' => (int)$a['updated_at'],
        'concluded_at' => $a['concluded_at'] !== null ? (int)$a['concluded_at'] : null,
        'concluded_by' => $staff ? ($a['concluded_by_name'] ?: null) : null,
        'reappeal_at'  => isset($a['reappeal_at']) && $a['reappeal_at'] !== null
                          ? (int)$a['reappeal_at'] : null,
        'overruled'    => isset($a['overruled_at']) && $a['overruled_at'] !== null
            ? ['at' => (int)$a['overruled_at'], 'by' => $a['overruled_by_name'] ?: null]
            : null,

        /* Every punishment under appeal, in the order they were issued.
           'punishment' stays as the first for anything that wants one. */
        'punishments' => array_map(function ($p) use ($showIssuer) {
            return punish_out($p, $showIssuer);
        }, $ps),
        'punishment' => $ps ? punish_out($ps[0], $showIssuer) : null,
        'accounts'   => $accounts,
        /* Staff only — null for the appellant, so there is no route by which
           their own page could draw it. */
        'whois'      => $whois,

        /* Platforms the appellant ticked that have nothing on file. Worth
           saying out loud: it is usually a misread of the question, and a
           handler who can't see the claim can't correct it. */
        'unmatched' => array_values(array_diff(
            appeal_platforms_in($a['platforms']),
            array_map(function ($p) { return punish_platform_of((string)$p['kind']); }, $ps)
        )),
        'evidence'   => appeal_evidence($pdo, (int)$a['id']),
        'comments'   => appeal_comments($pdo, (int)$a['id'], $staff, $a),
        /* What else this person has appealed. The appellant sees their own
           history; a handler sees the same list with who decided each. */
        'history'    => appeal_history($pdo, (int)$a['account_id'], (int)$a['id'], $staff),

        /* Characters aren't linked to the UCP. The field is reported as
           unavailable rather than omitted, so the page can draw it
           disabled with the reason instead of silently renumbering the
           form when it arrives. */
        'character'  => null,
        'features'   => ['characters' => false],
    ];

    if ($staff) {
        $block = appeal_conclude_block($pdo, $acc, $a, $ps ?: null);
        $out['log']    = appeal_log($pdo, (int)$a['id']);
        $out['viewer'] = [
            'staff'        => true,
            /* Their own appeal, seen from the staff side. Only Management
               and Founders ever get here; the page draws a warning rather
               than a lock, and every action carries their name in the log. */
            'own'          => $mine,
            'may_conclude' => $block === null,
            'why'          => $block,
            'may_comment'  => appeal_may_act($acc, $a),
            'may_manage'   => appeal_may_manage($acc),
            'is_handler'   => (int)($a['handler_id'] ?? 0) === (int)$acc['id'],
            'manage_rank'  => rank_name(BS_APPEAL_MANAGE_RANK),
            /* Overruling is only ever offered on a rejected appeal — there is
               nothing to overturn on one that was accepted. */
            'may_overrule' => $a['status'] === 'rejected' && appeals_has_waits($pdo)
                              && appeal_may_overrule($pdo, $acc)
                              && ((int)$acc['id'] !== (int)$a['account_id']
                                  || appeal_sees_own_as_staff($acc)),
            'waits'        => appeals_has_waits($pdo) ? appeal_wait_options() : [],
            'wait_suggest' => appeal_suggested_wait($pdo, (int)$a['account_id']),
        ];
    } else {
        $out['viewer'] = ['staff' => false, 'own' => $mine, 'may_conclude' => false,
                          'why' => null, 'may_comment' => $mine, 'may_manage' => false,
                          'may_overrule' => false, 'is_handler' => false];
    }

    return $out;
}
