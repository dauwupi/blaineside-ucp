<?php
/**
 * POST /api/login.php
 * Body: { username, password, remember? }
 * Verifies credentials, blocks unverified accounts, starts a session.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
throttle('login', 10);

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
    fail('Enter your username and password.');
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, password_hash, admin_rank, status
       FROM ucp_accounts WHERE username_lower = ? LIMIT 1'
);
$stmt->execute([strtolower($username)]);
$acc = $stmt->fetch();

// Uniform "invalid" message so we don't reveal which accounts exist.
if (!$acc || !password_verify($password, $acc['password_hash'])) {
    fail('Incorrect username or password.', 401);
}

// Must have verified their email.
if ($acc['status'] === 'pending') {
    fail('Please verify your email before signing in. Check your inbox, or resend the link.', 403);
}
if ($acc['status'] === 'suspended') {
    fail('This account is suspended. Contact staff on Discord.', 403);
}

// Success — establish the session.
$rank = (int)$acc['admin_rank'];
session_regenerate_id(true);
$_SESSION['uid']  = (int)$acc['id'];
$_SESSION['name'] = $acc['username'];
$_SESSION['rank'] = $rank;

$pdo->prepare('UPDATE ucp_accounts SET last_login = NOW() WHERE id = ?')->execute([$acc['id']]);

ok([
    'id'   => (int)$acc['id'],       // Account ID
    'name' => $acc['username'],
    'rank' => $rank,                 // 0–9
    'role' => rank_name($rank),      // display name ('' for Members)
    'redirect' => 'dashboard.html',
]);
