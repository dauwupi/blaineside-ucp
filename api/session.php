<?php
/**
 * GET /api/session.php
 * Returns the currently signed-in user, or { ok:false } if not logged in.
 * The dashboard calls this on load to get the real UCP name / Account ID.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';

if (empty($_SESSION['uid'])) {
    json_out(['ok' => false, 'authenticated' => false], 200);
}

// Re-read from DB so rank/status changes take effect without re-login.
$pdo  = db();
$stmt = $pdo->prepare('SELECT id, username, admin_rank, status, session_epoch FROM ucp_accounts WHERE id = ? LIMIT 1');
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

$rank = (int)$acc['admin_rank'];
ok([
    'authenticated' => true,
    'id'     => (int)$acc['id'],
    'name'   => $acc['username'],
    'rank'   => $rank,
    'role'   => rank_name($rank),
    'remember' => !empty($_SESSION['remember']),
]);
