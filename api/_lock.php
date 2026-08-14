<?php
/**
 * BlaineSide UCP — user locks.
 *
 * A lock is not a suspension. A suspension is the end of the conversation;
 * a lock is the start of one — the account is held while something is looked
 * into, the player is told why, and they get a route to appeal it.
 *
 * The enforcement is deliberately boring: locking sets status to 'locked',
 * and api/_account.php already refuses every authenticated request from an
 * account that isn't 'active'. There is no second list of places to check,
 * which means there is no second list of places to forget.
 *
 * Include AFTER _bootstrap.php and _ranks.php.
 */

declare(strict_types=1);

/**
 * Minimum rank that may lock or unlock: 6 = Senior Admin.
 *
 * Higher than the rest of the admin tools on purpose. Everything else here
 * is reading; this is the one action that takes somebody's access away, and
 * it should sit with the half of the ladder that carries that weight.
 */
const BS_LOCK_MIN_RANK = 6;

/** Longest reason. It is shown to the player, so it has to fit on a card. */
const BS_LOCK_REASON_MAX = 190;


/** May this rank lock accounts at all? */
function lock_may_manage(int $rank): bool
{
    return $rank >= BS_LOCK_MIN_RANK;
}

/** Has docs/migration-userlock.sql been run? */
function lock_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT locked_at FROM ucp_accounts LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}

/**
 * Whether this actor may lock or unlock this account — and why not.
 *
 * Returns an explanation, or null when it's allowed.
 *
 * The rules:
 *   Nobody locks themselves. There is no way back in afterwards.
 *   Nobody locks a rank equal to or above their own. A Senior Admin who
 *     falls out with a Lead Admin does not get to end the argument by
 *     locking them out, and neither does a Manager with a Founder.
 *   Founders can lock anyone but themselves.
 */
function lock_block_reason(array $actor, array $target): ?string
{
    $ar = (int)$actor['admin_rank'];
    $tr = (int)$target['admin_rank'];

    if ((int)$actor['id'] === (int)$target['id']) {
        return 'You can\'t lock your own account.';
    }
    if (!lock_may_manage($ar)) {
        return 'Locking an account is for ' . rank_name(BS_LOCK_MIN_RANK) . ' and above.';
    }
    if ($ar >= 9) return null;                 // Founder
    if ($tr >= $ar) {
        return 'You can only lock accounts in a group below your own.';
    }
    return null;
}

/** The lock, as the pages want it. Null when the account isn't locked. */
function lock_state(array $acc): ?array
{
    if (($acc['status'] ?? '') !== 'locked') return null;
    return [
        'at'      => isset($acc['locked_at']) && $acc['locked_at'] !== null ? (int)$acc['locked_at'] : null,
        'by'      => $acc['locked_by_name'] ?? null,
        'reason'  => $acc['lock_reason'] ?? null,
    ];
}

/**
 * The message a locked player sees when they try to sign in.
 *
 * Says what happened, who to talk to, and — when one was given — why. A lock
 * with no explanation is indistinguishable from a broken site, and the person
 * on the other end will go looking for the wrong problem.
 */
function lock_message(array $acc): string
{
    $reason = trim((string)($acc['lock_reason'] ?? ''));
    $msg = 'This account is locked.';
    if ($reason !== '') $msg .= ' ' . $reason;
    return $msg . ' You can appeal it through Reports & Appeals.';
}
