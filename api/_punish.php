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
 * The kinds, in the order they appear on the form.
 *
 * `platform` is what the player ticks in question 1. Two kinds could in
 * principle share one — they don't today — which is why the mapping is
 * explicit rather than the key doing double duty.
 */
function punish_kinds(): array
{
    return [
        'game_ban' => [
            'label'    => 'Game',
            'platform' => 'game',
            'noun'     => 'in-game ban',
            'live'     => false,
            'why'      => 'The game server isn\'t linked to the UCP yet, so in-game bans '
                        . 'aren\'t recorded here.',
        ],
        'user_lock' => [
            'label'    => 'Game',
            'platform' => 'game',
            'noun'     => 'user lock',
            'live'     => true,
            'why'      => null,
        ],
        'discord_ban' => [
            'label'    => 'Discord',
            'platform' => 'discord',
            'noun'     => 'Discord ban',
            'live'     => false,
            'why'      => 'Discord bans aren\'t recorded in the UCP yet — they are entered by '
                        . 'staff by hand until the bot is connected.',
        ],
        'forum_ban' => [
            'label'    => 'Forums',
            'platform' => 'forums',
            'noun'     => 'forum ban',
            'live'     => false,
            'why'      => 'Forum bans aren\'t recorded in the UCP yet — they are entered by '
                        . 'staff by hand until the forum link is built.',
        ],
    ];
}

/** The three tick-boxes on question 1, in order. */
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

/** Does the punishments table exist? False until migration-appeals.sql runs. */
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
function punish_out(array $p, bool $showIssuer = false): array
{
    $perm = !empty($p['permanent']);
    return [
        'id'         => (int)$p['id'],
        'kind'       => (string)$p['kind'],
        'noun'       => punish_kind_label((string)$p['kind']),
        'platform'   => punish_platform_of((string)$p['kind']),
        'permanent'  => $perm,
        'expires_at' => $p['expires_at'] !== null ? (int)$p['expires_at'] : null,
        'reason'     => $p['reason'] !== null && $p['reason'] !== '' ? (string)$p['reason'] : null,
        'issued_at'  => (int)$p['issued_at'],
        'issued_by'  => $showIssuer ? ($p['issued_by_name'] ?: null) : null,
        'active'     => punish_in_force($p),
        'appealable' => !empty($p['appealable']),
        'lifted_at'  => $p['lifted_at'] !== null ? (int)$p['lifted_at'] : null,
        'lifted_by'  => $p['lifted_by_name'] ?: null,
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

function record_for(PDO $pdo, int $accountId, bool $showIssuer = false,
                    ?array $viewer = null): array
{
    if (!punish_available($pdo)) {
        return ['available' => false, 'entries' => [], 'standing' => null,
                'last_at' => null, 'not_recorded' => record_not_recorded()];
    }

    $st = $pdo->prepare(
        'SELECT * FROM ucp_punishments WHERE account_id = ?
          ORDER BY issued_at DESC, id DESC'
    );
    $st->execute([$accountId]);
    $rows = $st->fetchAll();

    /* The appeal against each one, if there was ever an appeal. Fetched in
     * a single pass rather than per row: a long record would otherwise be
     * one query per entry, and this page is opened for exactly the accounts
     * with long records. */
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

    $now      = time();
    $entries  = [];
    $active   = 0;
    $recent   = 0;                                        // issued in the last 30 days
    $lastAt   = null;

    foreach ($rows as $p) {
        $inForce = punish_in_force($p);
        if ($inForce) $active++;
        if ((int)$p['issued_at'] > $now - 2592000) $recent++;
        if ($lastAt === null) $lastAt = (int)$p['issued_at'];

        $e = punish_out($p, $showIssuer);
        $e['appeal']     = $appeals[(int)$p['id']] ?? null;
        $e['can_edit']   = record_may_edit($viewer, $p);
        $e['can_delete'] = record_may_delete($viewer);
        $e['edited_at']  = isset($p['edited_at']) && $p['edited_at'] !== null
                             ? (int)$p['edited_at'] : null;
        $e['edited_by']  = isset($p['edited_by_name']) && $p['edited_by_name'] !== ''
                             ? (string)$p['edited_by_name'] : null;
        $entries[] = $e;
    }

    return [
        'available'    => true,
        'entries'      => $entries,
        'last_at'      => $lastAt,
        'standing'     => record_standing($active, $recent, count($entries)),
        'not_recorded' => record_not_recorded(),
    ];
}

/**
 * Where they stand, in one line.
 *
 * Three states, and the middle one matters most: somebody whose ban expired
 * last week is not in the same position as somebody with a clean sheet, and
 * saying "in good standing" to both would make the phrase worthless.
 */
function record_standing(int $active, int $recent, int $total): array
{
    if ($active > 0) {
        return [
            'level' => 'held',
            'title' => $active === 1 ? 'One punishment in force' : $active . ' punishments in force',
            'note'  => 'Standing is held while anything is in force. It clears on its own when '
                     . 'the punishment ends or is lifted.',
        ];
    }
    if ($recent > 0) {
        return [
            'level' => 'watch',
            'title' => 'Recent entries on the record',
            'note'  => $recent . ' ' . ($recent === 1 ? 'entry was' : 'entries were')
                     . ' added in the last 30 days. Nothing is in force. Entries stop counting '
                     . 'towards standing once they are 30 days old.',
        ];
    }
    return [
        'level' => 'good',
        'title' => 'In good standing',
        'note'  => $total === 0
            ? 'Nothing on the record.'
            : 'Nothing in force and nothing in the last 30 days. Older entries stay on the '
            . 'record but no longer count against standing.',
    ];
}

/** What this record cannot show yet, said out loud. */
function record_not_recorded(): array
{
    return [
        ['label' => 'Warnings', 'why' => 'Not recorded in the UCP yet.'],
        ['label' => 'Kicks',    'why' => 'Not recorded in the UCP yet.'],
    ];
}
