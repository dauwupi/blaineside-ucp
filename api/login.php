<?php
/**
 * POST /api/login.php
 * Body: { username, password, remember? }
 * Verifies credentials, blocks unverified accounts, starts a session.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('login', 10);

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
    fail('Enter your username and password.');
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, password_hash, admin_rank, status, session_epoch
       FROM ucp_accounts WHERE username_lower = ? LIMIT 1'
);
$stmt->execute([strtolower($username)]);
$acc = $stmt->fetch();

// ---- Server-side lockout (enforced here, not in the browser) ----
$ip        = client_ip();
$accountId = $acc ? (int)$acc['id'] : null;
// Unknown names get their own bucket, keyed on the name itself, so they
// throttle exactly like real ones and can't be told apart by the response.
$probe = $acc ? '' : hash('sha256', strtolower($username));

$lockLeft = lock_seconds_left($pdo, $accountId, $ip, $probe);
if ($lockLeft > 0) {
    json_out([
        'ok'           => false,
        'error'        => 'Too many attempts. Try again shortly.',
        'locked'       => true,
        'locked_for'   => $lockLeft,
    ], 429);
}

// Uniform "invalid" message so we don't reveal which accounts exist.
//
// The comparison always runs a real bcrypt verify, even when the account
// doesn't exist. Short-circuiting on !$acc returned in well under a
// millisecond while a real account took the full bcrypt cost — a timing
// difference big enough to enumerate valid UCP names over the network.
// DUMMY_HASH is a valid bcrypt hash of a value nobody can supply.
$dummyHash = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
$okPassword = password_verify($password, $acc['password_hash'] ?? $dummyHash);

if (!$acc || !$okPassword) {
    $lockedFor = record_failure($pdo, $accountId, $ip, $probe);
    json_out([
        'ok'         => false,
        'error'      => 'Incorrect UCP name or password.',
        'locked'     => $lockedFor > 0,
        'locked_for' => $lockedFor,
        'left'       => $lockedFor > 0 ? 0 : attempts_left($pdo, $accountId, $ip, $probe),
    ], $lockedFor > 0 ? 429 : 401);
}

// Must have verified their email.
if ($acc['status'] === 'pending') {
    fail('Please verify your email before signing in. Check your inbox, or resend the link.', 403);
}
if ($acc['status'] === 'suspended') {
    fail('This account is suspended. Contact staff on Discord.', 403);
}

// Success — establish the session.
clear_failures($pdo, $accountId, $ip, '');
$rank     = (int)$acc['admin_rank'];
$remember = !empty($in['remember']);
session_regenerate_id(true);
$_SESSION['uid']      = (int)$acc['id'];
$_SESSION['name']     = $acc['username'];
$_SESSION['rank']     = $rank;
$_SESSION['epoch']    = (int)($acc['session_epoch'] ?? 0);
$_SESSION['remember'] = $remember;

// If "remember me" was checked, issue a persistent token stored in the DB.
// The bootstrap picks this up on future requests even after the PHP session expires.
if ($remember) {
    // Remember-me must never be able to break a sign-in that has already
    // succeeded. If the token can't be stored (missing column, DB hiccup),
    // log it and continue — the user still gets their normal session.
    try {
        $rm_token   = bin2hex(random_bytes(32));
        $rm_expires = time() + 30 * 24 * 3600;
        $pdo->prepare(
            'UPDATE ucp_accounts SET remember_token = ?, remember_expires = ? WHERE id = ?'
        )->execute([token_hash($rm_token), $rm_expires, (int)$acc['id']]);

        $secure = is_https();
        // Persistent remember-me cookie (read by _bootstrap.php to restore the session).
        setcookie('bsucp_rm', $rm_token, [
            'expires'  => $rm_expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
        // Also extend the session cookie so it survives beyond the browser session.
        setcookie(session_name(), session_id(), [
            'expires'  => $rm_expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
    } catch (Throwable $e) {
        error_log('UCP remember-me could not be stored: ' . $e->getMessage());
    }
}

// ---- "Last sign-in …" notice -------------------------------------------
// Capture the PREVIOUS sign-in before we overwrite it, and remember this
// device so the login page can say "from this device" next time.
$prev = $pdo->prepare('SELECT last_login, last_device FROM ucp_accounts WHERE id = ? LIMIT 1');
$prev->execute([(int)$acc['id']]);
$prevRow    = $prev->fetch();
$prevLogin  = $prevRow['last_login'] ?? null;
$prevDevice = $prevRow['last_device'] ?? null;

// A per-device token, kept in a long-lived cookie and hashed in the DB.
$deviceRaw = $_COOKIE['bsucp_dev'] ?? '';
if (!preg_match('/^[a-f0-9]{32}$/', $deviceRaw)) {
    $deviceRaw = bin2hex(random_bytes(16));
}
$deviceHash = hash('sha256', $deviceRaw);
$sameDevice = ($prevDevice !== null && hash_equals((string)$prevDevice, $deviceHash));

$secureCookie = is_https();
setcookie('bsucp_dev', $deviceRaw, [
    'expires'  => time() + 90 * 24 * 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secureCookie,
]);
// Readable by the login page (not httponly) purely to render the notice.
// Contains only a timestamp + a flag — no account identifiers.
// $sameDevice was computed above but thrown away here — the cookie always
// claimed "same", so the login page's "from this device" line was shown to
// everyone, including someone signing in from a machine they'd never used.
setcookie('bsucp_last', json_encode([
    'ts'   => time(),
    'same' => $sameDevice,
]), [
    'expires'  => time() + 90 * 24 * 3600,
    'path'     => '/',
    'httponly' => false,
    'samesite' => 'Lax',
    'secure'   => $secureCookie,
]);

$pdo->prepare('UPDATE ucp_accounts SET last_login = NOW(), last_device = ? WHERE id = ?')
    ->execute([$deviceHash, (int)$acc['id']]);

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
        // '?' not '&' — the old URL put the query in the path, so this
        // lookup could never succeed. Which meant it also never stopped
        // retrying: forum_member_id stayed NULL, so EVERY subsequent login
        // paid for the request again.
        $lookup = $ips_url . '/core/members?' . http_build_query(['key' => $ips_key, 'email' => $fmData['email']]);
        $ch = curl_init($lookup);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            // Sign-in blocks on this. 10s meant a slow or unreachable forum
            // held the user on a spinner for ten seconds before letting them
            // in; 3s is plenty for a same-provider API call and the lookup is
            // optional anyway.
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            // The API key rides in the query string, so following a redirect
            // would hand it to whatever host the redirect names.
            CURLOPT_FOLLOWLOCATION => false,
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
    'redirect' => '/dashboard',
    'last_login'  => $prevLogin,
    'same_device' => $sameDevice,
]);
