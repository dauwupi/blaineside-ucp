<?php
/**
 * POST /api/member-lock.php
 * Body: { id, locked: true|false, reason?: "..." }
 *
 * Locks or unlocks one account. Senior Admin and above — see _lock.php for
 * the rules and why the bar is higher than the rest of the admin tools.
 *
 * Locking does two things: sets the status, and bumps session_epoch. The
 * second is what makes it immediate — without it the person stays signed in
 * on the tab they already have open until it happens to reload.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_lock.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_punish.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('member_lock', 20);

$pdo = db();
$acc = current_account($pdo);

if (!lock_may_manage((int)$acc['admin_rank'])) {
    json_out([
        'ok'    => false,
        'error' => 'Locking an account is for ' . rank_name(BS_LOCK_MIN_RANK) . ' and above.',
    ], 403);
}
if (!lock_available($pdo)) {
    json_out([
        'ok'    => false,
        'error' => 'User locks aren\'t set up on this server yet — docs/migration-userlock.sql '
                 . 'hasn\'t been run.',
    ], 409);
}

$in     = read_input();
$id     = (int)($in['id'] ?? 0);
$want   = !empty($in['locked']);
$reason = trim((string)($in['reason'] ?? ''));

$st = $pdo->prepare(
    'SELECT id, username, admin_rank, status, session_epoch
       FROM ucp_accounts WHERE id = ? LIMIT 1'
);
$st->execute([$id]);
$target = $st->fetch();
if (!$target) fail('That account no longer exists.', 404);

$block = lock_block_reason($acc, $target);
if ($block !== null) {
    json_out(['ok' => false, 'error' => $block], 403);
}

$now      = (string)$target['status'];
$isLocked = $now === 'locked';

if ($want && $isLocked) {
    json_out(['ok' => false, 'error' => $target['username'] . ' is already locked.'], 409);
}
if (!$want && !$isLocked) {
    json_out(['ok' => false, 'error' => $target['username'] . ' isn\'t locked.'], 409);
}

/* A suspension is a heavier state than a lock, and this endpoint is not the
 * place to quietly downgrade one. Somebody unsuspending an account should be
 * doing it deliberately, wherever that is done. */
if ($want && $now === 'suspended') {
    json_out(['ok' => false,
              'error' => $target['username'] . ' is suspended, which is already stronger than a lock.'], 409);
}
if ($want && $now === 'pending') {
    json_out(['ok' => false,
              'error' => $target['username'] . ' has never verified their email, so there is nothing to lock yet.'], 409);
}

if ($want) {
    if (mb_strlen($reason) > BS_LOCK_REASON_MAX) {
        $reason = mb_substr($reason, 0, BS_LOCK_REASON_MAX);
    }

    /* session_epoch is bumped in the same statement as the status.
     *
     * status alone ends their access on the next request, which is usually
     * within seconds — but "usually" is not what you want from a lock. The
     * epoch is checked by current_account() against the one stored in their
     * session, so bumping it invalidates every signed-in tab everywhere at
     * the instant this runs. */
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET status = \'locked\', locked_at = ?, locked_by = ?, locked_by_name = ?,
                lock_reason = ?, session_epoch = session_epoch + 1,
                remember_token = NULL, remember_expires = NULL
          WHERE id = ?'
    )->execute([time(), (int)$acc['id'], (string)$acc['username'],
                $reason !== '' ? $reason : null, $id]);

    // And the remembered devices, or the cookie would let them straight back.
    if (function_exists('sessions_revoke_others')) {
        try { $pdo->prepare('UPDATE ucp_sessions SET revoked_at = ? WHERE account_id = ? AND revoked_at IS NULL')
                  ->execute([time(), $id]); } catch (Throwable $e) {}
    }

    /* The punishment record.
     *
     * The columns on ucp_accounts are what the sign-in page reads to explain
     * the lock; this row is what an APPEAL attaches to. They are the same
     * fact written twice on purpose — the account carries the current state,
     * the punishments table carries the history, and an appeal has to point
     * at something that doesn't disappear the moment the lock is lifted.
     *
     * Guarded: the table arrives with docs/migration-appeals.sql. One
     * migration behind, the lock still works and simply isn't appealable
     * yet — which is exactly what the appeal page will then say. */
    if (punish_available($pdo)) {
        punish_add($pdo, $id, 'user_lock', [
            'permanent'      => true,
            'reason'         => $reason !== '' ? $reason : null,
            'issued_by'      => (int)$acc['id'],
            'issued_by_name' => (string)$acc['username'],
        ]);
    }

    security_log($pdo, $id, 'account_locked',
        'Locked by ' . $acc['username'] . ($reason !== '' ? ' — ' . $reason : ''), 'warn');

    ok([
        'id'      => $id,
        'locked'  => true,
        'message' => $target['username'] . ' is now locked. They\'ll be told why when they try to sign in.',
    ]);
}

// ---- Unlock ----------------------------------------------------------------
$pdo->prepare(
    'UPDATE ucp_accounts
        SET status = \'active\', locked_at = NULL, locked_by = NULL,
            locked_by_name = NULL, lock_reason = NULL
      WHERE id = ?'
)->execute([$id]);

// The lock is over, so the punishment it recorded is too.
punish_lift_kind($pdo, $id, 'user_lock', (int)$acc['id'], (string)$acc['username'],
                 'Unlocked by ' . $acc['username']);

security_log($pdo, $id, 'account_unlocked', 'Unlocked by ' . $acc['username'], 'good');

ok([
    'id'      => $id,
    'locked'  => false,
    'message' => $target['username'] . ' is unlocked and can sign in again.',
]);
