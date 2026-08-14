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
