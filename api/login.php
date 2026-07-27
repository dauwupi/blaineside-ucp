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

// If "remember me" was checked, extend the session cookie to 30 days.
if ($remember) {
    setcookie(session_name(), session_id(), [
        'expires'  => time() + 30 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
    ]);
}

$pdo->prepare('UPDATE ucp_accounts SET last_login = NOW() WHERE id = ?')->execute([$acc['id']]);

// ── Lazy forum_member_id population ──────────────────────────────────────────
// If the user has logged into the forum via OAuth at least once, IPS will have
// created their forum account. Look it up by email and store it now.
$fmRow = $pdo->prepare('SELECT forum_member_id, email FROM ucp_accounts WHERE id = ? LIMIT 1');
$fmRow->execute([$acc['id']]);
$fmData = $fmRow->fetch();

if ($fmData && $fmData['forum_member_id'] === null) {
    $ips_url = rtrim($CONFIG['ips']['url'] ?? '', '/');
    $ips_key = $CONFIG['ips']['key'] ?? '';
    if ($ips_url !== '' && $ips_key !== '') {
        $lookup = $ips_url . '/core/members&' . http_build_query(['key' => $ips_key, 'email' => $fmData['email']]);
        $ch = curl_init($lookup);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200 && $body) {
            $data = json_decode($body, true);
            if (isset($data['results'][0]['id'])) {
                $pdo->prepare('UPDATE ucp_accounts SET forum_member_id = ? WHERE id = ?')
                    ->execute([$data['results'][0]['id'], $acc['id']]);
            }
        }
    }
}

ok([
    'id'   => (int)$acc['id'],       // Account ID
    'name' => $acc['username'],
    'rank' => $rank,                 // 0–9
    'role' => rank_name($rank),      // display name ('' for Members)
    'redirect' => 'dashboard.html',
]);
