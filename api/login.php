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
$rank     = (int)$acc['admin_rank'];
$remember = !empty($in['remember']);
session_regenerate_id(true);
$_SESSION['uid']      = (int)$acc['id'];
$_SESSION['name']     = $acc['username'];
$_SESSION['rank']     = $rank;
$_SESSION['remember'] = $remember;

$secure = (($_SERVER['HTTPS'] ?? '') === 'on');

if ($remember) {
    // Generate a cryptographically random 64-char token and store it in the DB.
    // The browser gets a bsucp_rm cookie; _bootstrap.php checks it on every request
    // so the session is transparently restored even after PHP GC clears the session file.
    $rm_token   = bin2hex(random_bytes(32));
    $rm_expires = time() + 30 * 24 * 3600;

    $pdo->prepare(
        'UPDATE ucp_accounts SET remember_token = ?, remember_expires = ? WHERE id = ?'
    )->execute([$rm_token, $rm_expires, (int)$acc['id']]);

    setcookie('bsucp_rm', $rm_token, [
        'expires'  => $rm_expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);

    // Also extend the session cookie to match so the browser keeps it across restarts.
    setcookie(session_name(), session_id(), [
        'expires'  => $rm_expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
}

$pdo->prepare('UPDATE ucp_accounts SET last_login = NOW() WHERE id = ?')->execute([$acc['id']]);

ok([
    'id'   => (int)$acc['id'],       // Account ID
    'name' => $acc['username'],
    'rank' => $rank,                 // 0–9
    'role' => rank_name($rank),      // display name ('' for Members)
    'redirect' => 'dashboard.html',
]);
