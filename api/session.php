<?php
/**
 * GET /api/session.php
 * Returns the currently signed-in user, or { ok:false } if not logged in.
 * The dashboard calls this on load to get the real UCP name / Account ID.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';

if (empty($_SESSION['uid'])) {
    // A half-finished two-factor sign-in is NOT a session — 'uid' is unset, so
    // everything downstream correctly treats this browser as signed out. It is
    // reported separately only so the login page can tell "you never signed
    // in" from "you're one code away" and reopen the prompt.
    json_out([
        'ok'            => false,
        'authenticated' => false,
        'pending_2fa'   => !empty($_SESSION['pending_2fa'])
                           && time() <= (int)($_SESSION['pending_2fa_exp'] ?? 0),
    ], 200);
}

// Re-read from DB so rank/status changes take effect without re-login.
$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, admin_rank, status, session_epoch, totp_enabled
       FROM ucp_accounts WHERE id = ? LIMIT 1'
);
$stmt->execute([$_SESSION['uid']]);
$acc = $stmt->fetch();

if (!$acc || $acc['status'] !== 'active') {
    // account gone or no longer active — drop the session
    $_SESSION = [];
    session_destroy();
    json_out(['ok' => false, 'authenticated' => false], 200);
}

// A password reset bumps session_epoch. Any session issued before that no
// longer matches and is refused, which is how a reset ends sessions on every
// other device the account was signed in on.
if ((int)($_SESSION['epoch'] ?? 0) !== (int)$acc['session_epoch']) {
    $_SESSION = [];
    session_destroy();
    json_out(['ok' => false, 'authenticated' => false], 200);
}

$rank    = (int)$acc['admin_rank'];
$enabled = !empty($acc['totp_enabled']);

ok([
    'authenticated' => true,
    'id'     => (int)$acc['id'],
    'name'   => $acc['username'],
    'rank'   => $rank,
    'role'   => rank_name($rank),
    'remember' => !empty($_SESSION['remember']),
    'twofa'    => $enabled,
    // Set only when security.totp_required_rank is configured and this rank
    // is at or above it. The dashboard uses it to send staff to /security.
    'twofa_setup_required' => twofa_is_required($rank) && !$enabled,
]);
