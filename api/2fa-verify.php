<?php
/**
 * POST /api/2fa-verify.php
 * Body: { code }
 *
 * Second half of a two-factor sign-in. Only reachable while
 * $_SESSION['pending_2fa'] is set by login.php, which means the password has
 * already been checked and nothing about this browser is privileged yet.
 *
 * Accepts either a six-digit authenticator code or a recovery code.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require __DIR__ . '/_login_finish.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('2fa_verify', 15);

/** Wipes the half-authenticated state and tells the page to start over. */
function pending_expired(string $why): void {
    unset(
        $_SESSION['pending_2fa'],
        $_SESSION['pending_2fa_exp'],
        $_SESSION['pending_2fa_tries'],
        $_SESSION['pending_remember'],
        $_SESSION['pending_name']
    );
    json_out(['ok' => false, 'restart' => true, 'error' => $why], 401);
}

$uid = (int)($_SESSION['pending_2fa'] ?? 0);
if ($uid <= 0) {
    pending_expired('Your sign-in has expired. Enter your username and password again.');
}

// The pending state is a standing invitation to guess codes for one account,
// so it is deliberately short-lived. Without this it would survive as long as
// the PHP session file did.
if (time() > (int)($_SESSION['pending_2fa_exp'] ?? 0)) {
    pending_expired('Your sign-in timed out. Enter your username and password again.');
}

$in   = read_input();
$code = trim((string)($in['code'] ?? ''));
if ($code === '') {
    fail('Enter the code from your authenticator app.');
}

$pdo = db();
$ip  = client_ip();

// Codes get their own lockout bucket so brute-forcing the second factor
// escalates the same way brute-forcing the password does (30s → 5m → 15m),
// and survives the attacker throwing the session cookie away.
$lockLeft = lock_seconds_left($pdo, $uid, $ip, '2fa');
if ($lockLeft > 0) {
    json_out([
        'ok'         => false,
        'error'      => 'Too many codes tried. Try again shortly.',
        'locked'     => true,
        'locked_for' => $lockLeft,
    ], 429);
}

$stmt = $pdo->prepare(
    'SELECT id, username, admin_rank, status, session_epoch,
            totp_enabled, totp_secret, totp_last_step
       FROM ucp_accounts WHERE id = ? LIMIT 1'
);
$stmt->execute([$uid]);
$acc = $stmt->fetch();

if (!$acc || $acc['status'] !== 'active' || empty($acc['totp_enabled'])) {
    // Account suspended, deleted, or 2FA switched off from another session
    // while this one was mid-sign-in.
    pending_expired('This account can no longer be signed in to. Start again.');
}

$method = twofa_check($pdo, $acc, $code);

if ($method === null) {
    $tries     = (int)($_SESSION['pending_2fa_tries'] ?? 0) + 1;
    $lockedFor = record_failure($pdo, $uid, $ip, '2fa');

    require_once __DIR__ . '/_sessions.php';
    security_log($pdo, $uid, 'signin_failed',
        'Wrong two-step code' . ($lockedFor > 0 ? ' — locked out temporarily' : ''), 'warn');
    $_SESSION['pending_2fa_tries'] = $tries;

    // Session-level cap on top of the IP lockout: burn the pending state
    // outright so the attacker has to get past the password again — which
    // re-arms the password lockout as well.
    if ($tries >= BS_2FA_MAX_TRIES || $lockedFor > 0) {
        unset(
            $_SESSION['pending_2fa'],
            $_SESSION['pending_2fa_exp'],
            $_SESSION['pending_2fa_tries'],
            $_SESSION['pending_remember'],
            $_SESSION['pending_name']
        );
        json_out([
            'ok'         => false,
            'restart'    => true,
            'locked'     => $lockedFor > 0,
            'locked_for' => $lockedFor,
            'error'      => 'Too many incorrect codes. Sign in again from the start.',
        ], 429);
    }

    json_out([
        'ok'    => false,
        'error' => 'That code is not right. Check your authenticator app and try again.',
        'left'  => max(0, BS_2FA_MAX_TRIES - $tries),
    ], 401);
}

// ---- Correct ---------------------------------------------------------------
$remember  = !empty($_SESSION['pending_remember']);
$remaining = twofa_backup_remaining($pdo, $uid);

// A recovery code was just spent. The page says so, and how many are left, so
// nobody discovers they've run out on the day they actually need one.
login_finish($pdo, $acc, $remember, [
    'used_backup_code' => ($method === 'backup'),
    'backup_remaining' => $remaining,
], $method === 'backup' ? 'backup' : 'totp');
