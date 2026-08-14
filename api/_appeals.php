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

/** How long a rejected appellant waits before appealing again. */
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

function appeals_missing_reason(): string
{
    return 'Ban appeals aren\'t set up on this server yet — docs/migration-appeals.sql '
         . 'hasn\'t been run.';
}

/** Support Staff and above. */
function appeal_is_staff(array $acc): bool
{
    return (int)($acc['admin_rank'] ?? 0) >= BS_APPEAL_STAFF_RANK;
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
    if ((int)$acc['id'] === (int)$appeal['account_id']) {
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

    if (!$active) {
        /* The page puts this under a heading that already says there is
           nothing to appeal. Repeating it there cost a line and said
           nothing, so this is only the part the heading doesn't cover. */
        $out['why'] = 'Kicks and warnings can\'t be appealed — only a ban or a user lock.';
        return $out;
    }

    $appealable = array_values(array_filter($active, function ($p) {
        return !empty($p['appealable']);
    }));
    if (!$appealable) {
        $out['why'] = 'This punishment was issued for an egregious violation and is not open to '
                    . 'appeal.';
        return $out;
    }

    // Rejected recently. The three months run from the verdict, not from
    // the ban, so a second rejection doesn't reset a punishment's clock.
    $st = $pdo->prepare(
        'SELECT concluded_at FROM ucp_appeals
          WHERE account_id = ? AND status = \'rejected\'
          ORDER BY concluded_at DESC LIMIT 1'
    );
    $st->execute([(int)$acc['id']]);
    $last = $st->fetch();
    if ($last && $last['concluded_at'] !== null) {
        $until = (int)$last['concluded_at'] + BS_APPEAL_COOLDOWN;
        if ($until > time()) {
            $out['cooldown'] = $until;
            $out['why'] = 'Your last appeal was denied. You can appeal again three months after '
                        . 'the verdict.';
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
    }
    return punish_active_for($pdo, (int)$appeal['account_id']);
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
function appeal_comments(PDO $pdo, int $appealId, bool $staff): array
{
    $sql = 'SELECT * FROM ucp_appeal_comments WHERE appeal_id = ?'
         . ($staff ? '' : ' AND staff_only = 0')
         . ' ORDER BY created_at ASC, id ASC';
    $st = $pdo->prepare($sql);
    $st->execute([$appealId]);

    return array_map(function ($c) {
        return [
            'id'         => (int)$c['id'],
            'author'     => (string)$c['author_name'],
            'staff'      => !empty($c['author_is_staff']),
            'staff_only' => !empty($c['staff_only']),
            'body'       => (string)$c['body'],
            'at'         => (int)$c['created_at'],
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
    $staff = appeal_is_staff($acc) && !$mine;

    $owner = null; $accounts = null;
    $st = $pdo->prepare(
        'SELECT username, forum_member_id, discord, discord_username, discord_linked_at
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
        $accounts = [
            'forum' => [
                'linked'    => $row['forum_member_id'] !== null,
                'member_id' => $row['forum_member_id'] !== null
                               ? (int)$row['forum_member_id'] : null,
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

        /* Every punishment under appeal, in the order they were issued.
           'punishment' stays as the first for anything that wants one. */
        'punishments' => array_map(function ($p) use ($showIssuer) {
            return punish_out($p, $showIssuer);
        }, $ps),
        'punishment' => $ps ? punish_out($ps[0], $showIssuer) : null,
        'accounts'   => $accounts,

        /* Platforms the appellant ticked that have nothing on file. Worth
           saying out loud: it is usually a misread of the question, and a
           handler who can't see the claim can't correct it. */
        'unmatched' => array_values(array_diff(
            appeal_platforms_in($a['platforms']),
            array_map(function ($p) { return punish_platform_of((string)$p['kind']); }, $ps)
        )),
        'evidence'   => appeal_evidence($pdo, (int)$a['id']),
        'comments'   => appeal_comments($pdo, (int)$a['id'], $staff),

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
            'may_conclude' => $block === null,
            'why'          => $block,
            'may_comment'  => appeal_may_act($acc, $a),
            'may_manage'   => appeal_may_manage($acc),
            'is_handler'   => (int)($a['handler_id'] ?? 0) === (int)$acc['id'],
            'manage_rank'  => rank_name(BS_APPEAL_MANAGE_RANK),
        ];
    } else {
        $out['viewer'] = ['staff' => false, 'may_conclude' => false, 'why' => null,
                          'may_comment' => $mine, 'may_manage' => false,
                          'is_handler' => false];
    }

    return $out;
}
