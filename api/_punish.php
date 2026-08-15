<?php
/**
 * BlaineSide UCP — punishments.
 *
 * What can be appealed, and what a punishment IS.
 *
 * Four kinds, and the list is closed on purpose. A kick and a warning are
 * not here because they are not appealable, and the appeal form is built
 * from this registry — so there is no way to reach a form for something
 * that cannot be appealed, and no separate list of exclusions to keep in
 * step with this one.
 *
 * `live` says whether the system that issues that kind is connected. Only
 * user locks are today: they are issued by the UCP itself, in
 * api/member-lock.php. Game, forum and Discord bans exist as a kind, can
 * be entered by hand, and will be imported when each link is built — the
 * appeal side does not change when they are.
 */

require_once __DIR__ . '/_ranks.php';

/**
 * The four kinds, in the order their cards appear on the record.
 *
 * `card` is which box on the Administrative Record the entry is drawn in.
 * There is one card per kind and no merged list — a ban and a kick are not
 * the same sort of thing and reading them in one column made them look like
 * they were.
 *
 * `counts` is whether the entry is part of the record SUMMARY. A user lock
 * is not: it stops an account signing in while something is looked into,
 * which is a restriction on access rather than a mark against the person.
 * Counting it would put a number on a screenshot that says something untrue
 * about them.
 *
 * `stateful` is whether Active / Ended means anything. A ban runs and then
 * stops; a lock holds until it is lifted. A warning or a kick is a note on
 * the record — it happened, and that is the whole of it. Giving those two a
 * status would be inventing a fact about them.
 *
 * `live` is whether anything writes this kind yet. Only user locks do: they
 * are issued by the UCP itself in api/member-lock.php. Bans, warnings and
 * kicks arrive with the game server link; the record is built for them now
 * so that nothing about this page changes when they do.
 *
 * Forum and Discord bans are deliberately absent. They are issued on those
 * platforms and live there, and a mirrored row in the UCP could silently
 * disagree with the thing it was describing. Appeals against them still
 * work — see api/appeal-submit.php, which only requires a punishment on
 * file for the in-game platform.
 */
function punish_kinds(): array
{
    return [
        'ban' => [
            'label'    => 'Ban',
            'card'     => 'ban',
            'platform' => 'game',
            'noun'     => 'ban',
            'counts'   => true,
            'stateful' => true,
            'appealable' => true,
            'live'     => false,
            'why'      => 'The game server isn\'t linked to the UCP yet, so bans aren\'t '
                        . 'recorded here.',
        ],
        'warning' => [
            'label'    => 'Warning',
            'card'     => 'warn',
            'platform' => 'game',
            'noun'     => 'warning',
            'counts'   => true,
            'stateful' => false,
            'appealable' => false,
            'live'     => false,
            'why'      => 'Warnings aren\'t recorded in the UCP yet.',
        ],
        'kick' => [
            'label'    => 'Kick',
            'card'     => 'kick',
            'platform' => 'game',
            'noun'     => 'kick',
            'counts'   => true,
            'stateful' => false,
            'appealable' => false,
            'live'     => false,
            'why'      => 'Kicks aren\'t recorded in the UCP yet.',
        ],
        'user_lock' => [
            'label'    => 'User lock',
            'card'     => 'lock',
            'platform' => 'game',
            'noun'     => 'user lock',
            'counts'   => false,
            'stateful' => true,
            'appealable' => true,
            'live'     => true,
            'why'      => null,
        ],
    ];
}

/**
 * The cards on the record, in order, with the wording each one carries.
 *
 * Both pages read this, so the staff view and the player's own view cannot
 * describe the same kind two different ways.
 */
function punish_cards(): array
{
    return [
        [
            'key'   => 'ban',
            'kind'  => 'ban',
            'title' => 'Bans',
            'lede'  => 'Bans issued in game. A ban ends on its own date unless it is permanent, '
                     . 'and stays on the record either way.',
            'blank' => 'No ban has ever been issued against this account.',
        ],
        [
            'key'   => 'warn',
            'kind'  => 'warning',
            'title' => 'Warnings',
            'lede'  => 'Formal warnings. A warning is a note on the record — it is not something '
                     . 'that runs and then ends, and it cannot be appealed. It never leaves the '
                     . 'record.',
            'blank' => 'No warning has ever been issued against this account.',
        ],
        [
            'key'   => 'kick',
            'kind'  => 'kick',
            'title' => 'Kicks',
            'lede'  => 'Kicks from the server. A kick is over the moment it happens — it is '
                     . 'logged here as a fact and nothing more, and it cannot be appealed.',
            'blank' => 'This account has never been kicked.',
        ],
        [
            'key'   => 'lock',
            'kind'  => 'user_lock',
            'title' => 'User locks',
            'lede'  => 'A user lock stops this account signing in to the UCP. It is a '
                     . 'restriction, not a punishment — it is counted nowhere in the record '
                     . 'summary and it does not affect standing. It stays until it is lifted.',
            'blank' => 'This account has never been locked.',
        ],
    ];
}

/** The three tick-boxes on the appeal form, in order. */
function punish_platforms(): array
{
    return [
        'game'    => 'Game',
        'discord' => 'Discord',
        'forums'  => 'Forums',
    ];
}

function punish_kind_label(string $kind): string
{
    $k = punish_kinds();
    return $k[$kind]['noun'] ?? $kind;
}

function punish_platform_of(string $kind): string
{
    $k = punish_kinds();
    return $k[$kind]['platform'] ?? 'game';
}

function punish_card_of(string $kind): string
{
    $k = punish_kinds();
    return $k[$kind]['card'] ?? 'ban';
}

/** Is this kind part of the record summary? False for user locks. */
function punish_counts(string $kind): bool
{
    $k = punish_kinds();
    return !empty($k[$kind]['counts']);
}

/** Does Active / Ended mean anything for this kind? */
function punish_stateful(string $kind): bool
{
    $k = punish_kinds();
    return !empty($k[$kind]['stateful']);
}

function punish_available(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try { $pdo->query('SELECT 1 FROM ucp_punishments LIMIT 1'); $ok = true; }
    catch (Throwable $e) { $ok = false; }
    return $ok;
}

/**
 * Is this row in force right now?
 *
 * `active` and the expiry are both checked. A temporary ban that has run
 * out is still active = 1 in the table until something sweeps it, and
 * nobody should be able to appeal a ban that has already ended — or, worse,
 * be told they are still banned when they aren't.
 */
function punish_in_force(array $p): bool
{
    if (empty($p['active'])) return false;
    if (!empty($p['permanent'])) return true;
    $exp = $p['expires_at'] !== null ? (int)$p['expires_at'] : 0;
    return $exp === 0 || $exp > time();
}

/** Everything currently in force against one account, newest first. */
function punish_active_for(PDO $pdo, int $accountId): array
{
    if (!punish_available($pdo)) return [];
    $st = $pdo->prepare(
        'SELECT * FROM ucp_punishments
          WHERE account_id = ? AND active = 1
          ORDER BY issued_at DESC'
    );
    $st->execute([$accountId]);
    return array_values(array_filter($st->fetchAll(), 'punish_in_force'));
}

function punish_by_id(PDO $pdo, int $id): ?array
{
    if (!punish_available($pdo)) return null;
    $st = $pdo->prepare('SELECT * FROM ucp_punishments WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * One punishment as the front end sees it.
 *
 * `issued_by_name` is included but the pages decide whether to show it —
 * see the "Enable banning admin name showing" action. By default a player
 * is not told which administrator banned them, because that turns an
 * appeal into a complaint about a person.
 */
function punish_out(array $p, bool $showIssuer = false, int $issuerRank = 0): array
{
    $kind = (string)$p['kind'];
    return [
        'id'         => (int)$p['id'],
        'kind'       => $kind,
        'label'      => punish_kinds()[$kind]['label'] ?? $kind,
        'noun'       => punish_kind_label($kind),
        'card'       => punish_card_of($kind),
        'platform'   => punish_platform_of($kind),
        'stateful'   => punish_stateful($kind),
        'permanent'  => !empty($p['permanent']),
        'expires_at' => $p['expires_at'] !== null ? (int)$p['expires_at'] : null,
        'reason'     => $p['reason'] !== null && $p['reason'] !== '' ? (string)$p['reason'] : null,
        'issued_at'  => (int)$p['issued_at'],
        'issued_by'  => $showIssuer ? ($p['issued_by_name'] ?: null) : null,
        /* The issuer's rank travels with the name so the page can colour it
           the same way the name is coloured everywhere else in the UCP. A
           name in the wrong colour is worse than a name in no colour. */
        'issued_rank'=> $showIssuer ? $issuerRank : 0,
        'active'     => punish_in_force($p),
        'appealable' => !empty($p['appealable']),
        'lifted_at'  => $p['lifted_at'] !== null ? (int)$p['lifted_at'] : null,
        'lifted_by'  => $showIssuer ? ($p['lifted_by_name'] ?: null) : null,
    ];
}

/**
 * Record a punishment. Returns its id.
 *
 * Called by api/member-lock.php when a user lock is issued, and by hand
 * for the kinds whose systems aren't connected. Deliberately not wrapped
 * in a "ban this person" helper — issuing a ban has to also do whatever
 * the platform needs, and that differs per platform.
 */
function punish_add(PDO $pdo, int $accountId, string $kind, array $opt = []): int
{
    $pdo->prepare(
        'INSERT INTO ucp_punishments
            (account_id, kind, permanent, expires_at, reason, issued_by, issued_by_name,
             issued_at, active, appealable, external_ref)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)'
    )->execute([
        $accountId, $kind,
        !empty($opt['permanent']) || empty($opt['expires_at']) ? 1 : 0,
        $opt['expires_at'] ?? null,
        $opt['reason'] ?? null,
        $opt['issued_by'] ?? null,
        $opt['issued_by_name'] ?? null,
        $opt['issued_at'] ?? time(),
        array_key_exists('appealable', $opt) ? (int)!empty($opt['appealable']) : 1,
        $opt['external_ref'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

/** Lift one punishment. */
function punish_lift(PDO $pdo, int $id, ?int $byId, ?string $byName, ?string $why = null): void
{
    $pdo->prepare(
        'UPDATE ucp_punishments
            SET active = 0, lifted_at = ?, lifted_by = ?, lifted_by_name = ?, lifted_reason = ?
          WHERE id = ? AND active = 1'
    )->execute([time(), $byId, $byName, $why, $id]);
}

/** Lift every active punishment of one kind against one account. */
function punish_lift_kind(PDO $pdo, int $accountId, string $kind,
                          ?int $byId, ?string $byName, ?string $why = null): void
{
    if (!punish_available($pdo)) return;
    $pdo->prepare(
        'UPDATE ucp_punishments
            SET active = 0, lifted_at = ?, lifted_by = ?, lifted_by_name = ?, lifted_reason = ?
          WHERE account_id = ? AND kind = ? AND active = 1'
    )->execute([time(), $byId, $byName, $why, $accountId, $kind]);
}

/**
 * Who may change what is on a record.
 *
 * Two separate powers, deliberately not one.
 *
 *   Editing is correcting the wording of a reason. The administrator who
 *   issued it may fix their own — they wrote it, they know what they meant,
 *   and making them queue for a Manager to fix a typo means the typo stays.
 *   They may not touch anybody else's: an administrator quietly rewording a
 *   colleague's ban reason is how a record stops being evidence.
 *
 *   Deleting removes the entry from the record entirely. That is the power
 *   to make a punishment never have happened, so it sits with Management and
 *   the Founder and nowhere else — including the administrator who issued
 *   it, who otherwise could erase their own mistakes before anybody read
 *   them.
 *
 * Both are refused outright on the player's own view of their record. The
 * flags are computed here, on the server, and the endpoints ask again — the
 * buttons the page draws are a convenience, not the rule.
 */
const BS_RECORD_ADMIN_RANK = 8;      // Management and the Founder

function record_may_delete(?array $viewer): bool
{
    return $viewer !== null && (int)($viewer['admin_rank'] ?? 0) >= BS_RECORD_ADMIN_RANK;
}

function record_may_edit(?array $viewer, array $p): bool
{
    if ($viewer === null) return false;
    if ((int)($viewer['admin_rank'] ?? 0) >= BS_RECORD_ADMIN_RANK) return true;
    $issuer = $p['issued_by'] !== null ? (int)$p['issued_by'] : 0;
    return $issuer > 0 && $issuer === (int)$viewer['id'];
}

/**
 * The administrative record for one account.
 *
 * Everything on file, with the appeal outcome attached to each entry, plus
 * a one-line reading of where they stand. This is what both the player's
 * own Standing tab and the staff Administrative Record are drawn from, so
 * the two cannot disagree about the same account.
 *
 * $showIssuer follows the rule the rest of the system follows: a player is
 * not told which administrator punished them, and staff always are.
 *
 * Warnings and kicks are deliberately NOT invented. They are not in
 * punish_kinds() because they are not appealable, and nothing records them
 * yet — so the record says so rather than showing a clean sheet that would
 * read as "this player has never been warned".
 */
/**
 * Has docs/migration-record-edit.sql been run?
 *
 * Same shape as the other capability guards: a server one migration behind
 * refuses the two endpoints with a sentence naming the file, instead of
 * throwing a 500 that reaches the page as "Something went wrong".
 */
function punish_edit_available(PDO $pdo): bool
{
    static $ok = null;
    if ($ok !== null) return $ok;
    try { $pdo->query('SELECT edited_at FROM ucp_punishments LIMIT 1'); $ok = true; }
    catch (Throwable $e) { $ok = false; }
    return $ok;
}

/**
 * One line in the punishment log.
 *
 * `snapshot` holds the entry as it was, as JSON. On an edit that is the old
 * and new wording; on a delete it is the whole entry, which is the only copy
 * left once the row is gone.
 */
function punish_log_add(PDO $pdo, array $p, array $actor, string $action,
                        ?string $detail = null, ?string $snapshot = null): void
{
    try {
        $pdo->prepare(
            'INSERT INTO ucp_punishment_log
                (punishment_id, account_id, action, actor_id, actor_name, detail, snapshot,
                 created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            (int)$p['id'], (int)$p['account_id'], $action,
            (int)$actor['id'], (string)$actor['username'], $detail, $snapshot, time(),
        ]);
    } catch (Throwable $e) {
        // The log table is part of the same migration as the columns, and the
        // endpoints refuse without it. Nothing useful to do here.
    }
}

/**
 * The administrative record for one account.
 *
 * Everything on file, grouped by the card it belongs to, plus the summary
 * panel at the top of the page. Both the player's own view and the staff
 * view are drawn from this one function, so the two cannot disagree about
 * the same account.
 *
 * The summary counts bans, warnings and kicks. It does not count user
 * locks — see punish_kinds() for why.
 *
 * $showIssuer follows the rule the rest of the system follows: a player is
 * not told which administrator punished them, and staff always are.
 */
function record_for(PDO $pdo, int $accountId, bool $showIssuer = false,
                    ?array $viewer = null): array
{
    if (!punish_available($pdo)) {
        return [
            'available' => false,
            'entries'   => [],
            'cards'     => punish_cards(),
            'counts'    => ['ban' => 0, 'warn' => 0, 'kick' => 0, 'lock' => 0],
            'summary'   => record_summary(0, 0, 0, 0, 0, null, null),
        ];
    }

    $st = $pdo->prepare(
        'SELECT * FROM ucp_punishments WHERE account_id = ?
          ORDER BY issued_at DESC, id DESC'
    );
    $st->execute([$accountId]);
    $rows = $st->fetchAll();

    /* The rank each issuing administrator holds now, fetched in one pass.
       A record with forty entries would otherwise be forty queries, and this
       page is opened for exactly the accounts with forty entries. */
    $ranks = [];
    if ($showIssuer && $rows) {
        $ids = [];
        foreach ($rows as $p) {
            if ($p['issued_by'] !== null) $ids[(int)$p['issued_by']] = true;
        }
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $rk = $pdo->prepare("SELECT id, admin_rank FROM ucp_accounts WHERE id IN ($in)");
            $rk->execute(array_keys($ids));
            foreach ($rk->fetchAll() as $r) $ranks[(int)$r['id']] = (int)$r['admin_rank'];
        }
    }

    /* The appeal against each entry, if there ever was one. */
    $appeals = [];
    try {
        $ap = $pdo->prepare(
            'SELECT ap.punishment_id, a.id, a.status
               FROM ucp_appeal_punishments ap
               JOIN ucp_appeals a ON a.id = ap.appeal_id
              WHERE a.account_id = ?
              ORDER BY a.created_at DESC'
        );
        $ap->execute([$accountId]);
        foreach ($ap->fetchAll() as $r) {
            $pid = (int)$r['punishment_id'];
            if (!isset($appeals[$pid])) {                 // newest wins
                $appeals[$pid] = ['id' => (int)$r['id'], 'status' => (string)$r['status']];
            }
        }
    } catch (Throwable $e) {
        // Appeals not migrated. The record still lists the punishments.
    }

    $now     = time();
    $entries = [];
    $counts  = ['ban' => 0, 'warn' => 0, 'kick' => 0, 'lock' => 0];
    $total   = 0;     // counted kinds only
    $active  = 0;     // active bans
    $recent  = 0;     // counted entries in the last 30 days
    $lastAt  = null;
    $firstAt = null;
    $bans = $warns = $kicks = 0;

    foreach ($rows as $p) {
        $kind = (string)$p['kind'];
        $rank = $p['issued_by'] !== null ? ($ranks[(int)$p['issued_by']] ?? 0) : 0;

        $e = punish_out($p, $showIssuer, $rank);
        $e['appeal']     = $appeals[(int)$p['id']] ?? null;
        $e['can_edit']   = record_may_edit($viewer, $p);
        $e['can_delete'] = record_may_delete($viewer);
        $e['edited_at']  = isset($p['edited_at']) && $p['edited_at'] !== null
                             ? (int)$p['edited_at'] : null;
        $e['edited_by']  = isset($p['edited_by_name']) && $p['edited_by_name'] !== ''
                             ? (string)$p['edited_by_name'] : null;
        $entries[] = $e;

        $counts[$e['card']] = ($counts[$e['card']] ?? 0) + 1;

        if (!punish_counts($kind)) continue;              // locks stop here

        $total++;
        if ($kind === 'ban')     $bans++;
        if ($kind === 'warning') $warns++;
        if ($kind === 'kick')    $kicks++;
        if ($kind === 'ban' && $e['active']) $active++;
        if ((int)$p['issued_at'] > $now - 2592000) $recent++;

        $at = (int)$p['issued_at'];
        if ($lastAt === null || $at > $lastAt)  $lastAt  = $at;
        if ($firstAt === null || $at < $firstAt) $firstAt = $at;
    }

    return [
        'available' => true,
        'entries'   => $entries,
        'cards'     => punish_cards(),
        'counts'    => $counts,
        'summary'   => record_summary($total, $active, $recent, $bans, $warns, $lastAt, $firstAt,
                                      $kicks),
    ];
}

/**
 * The summary panel, worked out here so both pages say the same thing.
 *
 * Three states, named for what they describe rather than for a score. "In
 * good standing" was a verdict on the person; "no recent administrative
 * punishments" is a fact about the record, and a fact is what a screenshot
 * of this page should be carrying.
 */
function record_summary(int $total, int $active, int $recent, int $bans, int $warns,
                        ?int $lastAt, ?int $firstAt, int $kicks = 0): array
{
    if ($active > 0) {
        $level = 'held';
        $head  = $active === 1 ? 'Punishment on record' : 'Punishments on record';
        $note  = ($active === 1 ? 'One ban is active.' : $active . ' bans are active.')
               . ' This clears on its own when the ban ends or is lifted.';
    } elseif ($recent > 0) {
        $level = 'watch';
        $head  = $recent === 1 ? 'Recent administrative punishment'
                               : 'Recent administrative punishments';
        $note  = $recent . ($recent === 1 ? ' entry was' : ' entries were')
               . ' added in the last 30 days. Nothing is active. Entries stop counting once they '
               . 'are 30 days old.';
    } else {
        $level = 'good';
        $head  = 'No recent administrative punishments';
        $note  = $total > 0
            ? 'Nothing active and nothing in the last 30 days. Older entries stay on the record '
            . 'but no longer count against it.'
            : 'Nothing has ever been issued against this account.';
    }

    return [
        'level'    => $level,
        'head'     => $head,
        'note'     => $note,
        'total'    => $total,
        'bans'     => $bans,
        'warnings' => $warns,
        'kicks'    => $kicks,
        'active'   => $active,
        'recent'   => $recent,
        'last_at'  => $lastAt,
        'first_at' => $firstAt,
    ];
}
