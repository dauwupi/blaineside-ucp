<?php
/**
 * POST /api/login.php
 * Body: { username, password, remember? }
 * Verifies credentials, blocks unverified accounts, starts a session.
 *
 * When the account has two-factor enabled this endpoint stops half way: it
 * records a pending state and answers { requires_2fa: true }. The session is
 * only established once /api/2fa-verify.php accepts a code.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_lock.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require __DIR__ . '/_login_finish.php';

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
    'SELECT id, username, password_hash, admin_rank, status, session_epoch,
            totp_enabled, totp_secret, totp_last_step
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

    // Only logged against a real account — a wrong guess at a name nobody
    // owns has no account to file it under, and inventing one would be a
    // free way to fill someone else's log with noise.
    if ($accountId !== null) {
        require_once __DIR__ . '/_sessions.php';
        security_log($pdo, $accountId, 'signin_failed',
            'Wrong password' . ($lockedFor > 0 ? ' — account locked for ' . $lockedFor . ' seconds' : ''),
            'warn');
    }
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

/* A lock is answered, not just refused.
 *
 * The password was right, so this is the account holder — telling them
 * nothing would send them round the reset-password loop for a problem a
 * password can't fix. They get what happened, why if a reason was given, and
 * where to appeal. `locked` in the response lets the sign-in page draw that
 * as a state rather than as another red validation error. */
if ($acc['status'] === 'locked') {
    // The reason lives in a column added by docs/migration-userlock.sql. The
    // status can't read 'locked' before that ran, but fetch it defensively
    // anyway — a missing reason should cost the wording, not the response.
    try {
        $rs = $pdo->prepare('SELECT lock_reason FROM ucp_accounts WHERE id = ? LIMIT 1');
        $rs->execute([(int)$acc['id']]);
        $acc['lock_reason'] = $rs->fetchColumn() ?: null;
    } catch (Throwable $e) {
        $acc['lock_reason'] = null;
    }

    json_out([
        'ok'     => false,
        'locked' => true,
        'reason' => $acc['lock_reason'],
        'error'  => lock_message($acc),
    ], 403);
}

// ---- Two-factor gate ------------------------------------------------------
//
// The password was right, so its failure counter is cleared here rather than
// after the code step — otherwise someone who genuinely has 2FA on but
// fumbles codes would rack up a password lockout too. Code attempts get their
// own bucket (probe '2fa') inside 2fa-verify.php.
//
// Note what is NOT set: 'uid' stays unset, so session.php, the OAuth
// authorize page and the dashboard all continue to treat this browser as
// signed out until the code lands. Half-authenticated means half — there is
// no window where a correct password alone grants anything.
if (!empty($acc['totp_enabled']) && !empty($acc['totp_secret'])) {
    clear_failures($pdo, $accountId, $ip, '');

    // Fresh session id before storing the pending state, so a session fixed by
    // an attacker beforehand isn't the one that gets promoted on success.
    session_regenerate_id(true);

    $_SESSION['pending_2fa']       = (int)$acc['id'];
    $_SESSION['pending_2fa_exp']   = time() + BS_PENDING_TTL;
    $_SESSION['pending_2fa_tries'] = 0;
    $_SESSION['pending_remember']  = !empty($in['remember']);
    $_SESSION['pending_name']      = $acc['username'];

    json_out([
        'ok'           => false,
        'requires_2fa' => true,
        'expires_in'   => BS_PENDING_TTL,
    ], 200);
}

// ---- No second factor — sign in now ---------------------------------------
login_finish($pdo, $acc, !empty($in['remember']));
