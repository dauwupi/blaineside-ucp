<?php
/**
 * Staff rank ladder. `admin_rank` (0–9) in the database maps to these names.
 * A higher number = more permissions. Use these helpers everywhere so the
 * names stay consistent across the UCP and the future admin panel.
 */

const RANKS = [
    0 => 'Member',
    1 => 'Support Staff',
    2 => 'Development Team',
    3 => 'Trainee Admin',
    4 => 'Admin Lvl 1',
    5 => 'Admin Lvl 2',
    6 => 'Senior Admin',
    7 => 'Lead Admin',
    8 => 'Management',
    9 => 'Founder',
];

/** Rank number → display name. Always returns a name (Members included). */
function rank_name(int $rank): string {
    return RANKS[$rank] ?? 'Member';
}

/** Is this rank staff (anything above Member)? */
function is_staff(int $rank): bool {
    return $rank >= 1;
}

/** Does `rank` meet or exceed the required level? For permission gating. */
function rank_at_least(int $rank, int $required): bool {
    return $rank >= $required;
}
