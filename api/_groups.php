<?php
/**
 * BlaineSide UCP — who may change whose group.
 *
 * The whole point of this file is that the rules live in one place. They are
 * enforced in api/member-rank.php on every request; the page uses the same
 * helpers only to decide what to offer, so what it shows and what the server
 * allows can't drift apart.
 *
 * The rules:
 *   Founder (9)     may set anyone to any group.
 *   Management (8)  may set anyone at group 7 or below, to group 7 or below.
 *                   Another Manager or a Founder is out of reach entirely.
 *   Nobody          may change their own group — including a Founder. There
 *                   is no undo in this interface, and an accidental
 *                   self-demotion by the only Founder is a database job to
 *                   recover. Ask someone else; that is the whole safeguard.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

/** Lowest rank that may reach group management at all. */
const BS_GROUPS_MIN_RANK = 8;

/** Ranks a Manager may neither hold-target nor hand out. */
const BS_GROUPS_RESERVED = 8;   // 8 = Management, 9 = Founder

function groups_may_manage(int $rank): bool
{
    return $rank >= BS_GROUPS_MIN_RANK;
}

function require_group_manager(array $acc): void
{
    if (!groups_may_manage((int)$acc['admin_rank'])) {
        json_out([
            'ok'    => false,
            'error' => 'Only Management and Founders can change groups.',
        ], 403);
    }
}

/** The ranks this actor is allowed to hand out. */
function groups_assignable(int $actorRank): array
{
    $max = $actorRank >= 9 ? 9 : BS_GROUPS_RESERVED - 1;
    return range(0, $max);
}

/**
 * Whether $actor may change $target's group at all — before we even look at
 * what they want to change it to.
 *
 * Returns an explanation, or null when it's allowed. The explanation is
 * shown to the person, so it says what the rule is rather than "forbidden".
 */
function groups_block_reason(array $actor, array $target): ?string
{
    if ((int)$actor['id'] === (int)$target['id']) {
        return 'You can\'t change your own group. Ask another Founder.';
    }
    if ((int)$actor['admin_rank'] >= 9) {
        return null;
    }
    if ((int)$target['admin_rank'] >= BS_GROUPS_RESERVED) {
        return 'Only a Founder can change the group of Management and Founders.';
    }
    return null;
}
