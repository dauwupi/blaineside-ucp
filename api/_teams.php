<?php
/**
 * BlaineSide UCP — administrator sub-groups.
 *
 * A sub-group is a department, not a rung. Staff Management, Faction
 * Management and Property Management sit alongside the group ladder: an
 * Admin Lvl 2 who holds Staff Management can open staff profiles that a
 * Senior Admin without it cannot. That is the whole point of them — the
 * ladder says how senior somebody is, a sub-group says what they look after.
 *
 * The band is deliberate. Sub-groups apply to Trainee Admin (3) through Lead
 * Admin (7) and nobody else:
 *
 *   Below Trainee Admin  — not an administrator. There is nothing to add to.
 *   Management, Founder  — already have everything a sub-group could grant,
 *                          so holding one would be noise on the account and
 *                          a second place to look when working out what
 *                          somebody can do.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

/** The rank band sub-groups apply to, inclusive. */
const BS_TEAM_MIN_RANK = 3;   // Trainee Admin
const BS_TEAM_MAX_RANK = 7;   // Lead Admin


/**
 * Every sub-group.
 *
 * `grants` is what it actually does today, in the present tense, and it has
 * to stay true. Faction and Property Management are real assignments — you
 * can hand them out now and the record is kept — but the systems they will
 * gate don't exist yet, so they say so rather than implying a power that
 * isn't wired to anything.
 *
 * Adding a fourth is an entry here. Nothing else needs to change: the
 * migration stores whatever key appears, and the page builds its toggles
 * from this list.
 */
function teams_registry(): array
{
    return [
        [
            'key'    => 'staff_management',
            'label'  => 'Staff Management',
            'blurb'  => 'Looks after the staff team itself.',
            'grants' => [
                'Can open staff accounts in Administrative Search, at any admin rank.',
            ],
            'live'   => true,
        ],
        [
            'key'    => 'faction_management',
            'label'  => 'Faction Management',
            'blurb'  => 'Looks after factions and their rosters.',
            'grants' => [],
            'live'   => false,
            'why'    => 'Factions aren\'t in the UCP yet, so this grants nothing today. '
                      . 'It is recorded now and will gate the faction tools when they arrive.',
        ],
        [
            'key'    => 'property_management',
            'label'  => 'Property Management',
            'blurb'  => 'Looks after properties and businesses.',
            'grants' => [],
            'live'   => false,
            'why'    => 'Properties aren\'t in the UCP yet, so this grants nothing today. '
                      . 'It is recorded now and will gate the property tools when they arrive.',
        ],
    ];
}

/** Just the keys, for validation. */
function teams_keys(): array
{
    return array_column(teams_registry(), 'key');
}

/** One entry by key, or null. */
function team_by(string $key): ?array
{
    foreach (teams_registry() as $t) if ($t['key'] === $key) return $t;
    return null;
}

/** The display name, or the raw key if it has been retired from the registry. */
function team_label(string $key): string
{
    $t = team_by($key);
    return $t ? $t['label'] : $key;
}


/**
 * Has docs/migration-subgroups.sql been run?
 *
 * Everything here degrades to "nobody holds a sub-group" when it hasn't,
 * rather than erroring — the same approach _sessions.php takes. A UCP that
 * is one migration behind should be missing a feature, not broken.
 */
function teams_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_account_teams LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}


/** May an account at this rank hold sub-groups at all? */
function team_eligible(int $rank): bool
{
    return $rank >= BS_TEAM_MIN_RANK && $rank <= BS_TEAM_MAX_RANK;
}

/** Why not, in a sentence somebody can act on. */
function team_ineligible_reason(int $rank): string
{
    if ($rank < BS_TEAM_MIN_RANK) {
        return 'Sub-groups are for administrators. Put them in an admin group first — '
             . rank_name(BS_TEAM_MIN_RANK) . ' or above.';
    }
    return rank_name($rank) . ' already has everything a sub-group grants, so they don\'t take one.';
}


/* =====================================================================
   READING
   ===================================================================== */

/** The sub-groups one account holds. Always an array. */
function teams_for(PDO $pdo, int $id): array
{
    if (!teams_available($pdo)) return [];
    try {
        $st = $pdo->prepare('SELECT team FROM ucp_account_teams WHERE account_id = ?');
        $st->execute([$id]);
        return array_map('strval', array_column($st->fetchAll(), 'team'));
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * The sub-groups for a page of accounts, keyed by account id.
 *
 * One query for the whole list rather than one per row — a fifteen-row
 * results table shouldn't make sixteen round trips to the database.
 */
function teams_map(PDO $pdo, array $ids): array
{
    $out = [];
    foreach ($ids as $id) $out[(int)$id] = [];
    if (!$ids || !teams_available($pdo)) return $out;

    try {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT account_id, team FROM ucp_account_teams WHERE account_id IN ($in)");
        $st->execute(array_map('intval', $ids));
        foreach ($st->fetchAll() as $r) $out[(int)$r['account_id']][] = (string)$r['team'];
    } catch (Throwable $e) {
        // Leave everyone with none — see teams_available().
    }
    return $out;
}

/** Does this account hold this sub-group? */
function has_team(PDO $pdo, int $id, string $key): bool
{
    return in_array($key, teams_for($pdo, $id), true);
}


/* =====================================================================
   WRITING
   ===================================================================== */

/**
 * Replaces an account's sub-groups with exactly this list.
 *
 * Returns [added, removed] as label arrays so the caller can write one
 * sensible log line rather than one per change.
 *
 * Rank is NOT checked here — the endpoint does that, because it is the one
 * with an actor to refuse and a message to show. This function is also used
 * by teams_clear_if_ineligible(), which runs precisely when the rank has
 * stopped being valid.
 */
function teams_set(PDO $pdo, int $id, array $wanted, array $actor): array
{
    if (!teams_available($pdo)) return [[], []];

    $valid   = teams_keys();
    $wanted  = array_values(array_unique(array_filter($wanted, function ($k) use ($valid) {
        return in_array($k, $valid, true);
    })));
    $current = teams_for($pdo, $id);

    $add    = array_values(array_diff($wanted, $current));
    $remove = array_values(array_diff($current, $wanted));
    if (!$add && !$remove) return [[], []];

    try {
        if ($remove) {
            $in = implode(',', array_fill(0, count($remove), '?'));
            $st = $pdo->prepare("DELETE FROM ucp_account_teams WHERE account_id = ? AND team IN ($in)");
            $st->execute(array_merge([$id], $remove));
        }
        foreach ($add as $key) {
            $st = $pdo->prepare(
                'INSERT INTO ucp_account_teams (account_id, team, granted_by, granted_by_name, granted_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $st->execute([$id, $key, (int)($actor['id'] ?? 0), (string)($actor['username'] ?? ''), time()]);
        }
    } catch (Throwable $e) {
        error_log('UCP teams_set: ' . $e->getMessage());
        return [[], []];
    }

    return [
        array_map('team_label', $add),
        array_map('team_label', $remove),
    ];
}

/**
 * Drops every sub-group from an account that is no longer allowed one.
 *
 * Called after a rank change. Without it, promoting a Lead Admin to
 * Management would leave sub-group rows behind that nothing displays and
 * nothing enforces — and which would quietly come back if they were ever
 * demoted again. A permission that reappears without anybody granting it is
 * the kind of thing nobody finds until it matters.
 */
function teams_clear_if_ineligible(PDO $pdo, int $id, int $newRank, array $actor): array
{
    if (team_eligible($newRank)) return [];
    list(, $removed) = teams_set($pdo, $id, [], $actor);
    return $removed;
}
