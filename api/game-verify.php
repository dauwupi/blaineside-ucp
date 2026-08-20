<?php
/**
 * POST /api/game-verify.php
 * Body: { username, password, code? }
 *
 * Server-to-server only: the FiveM server calls this to check an in-game
 * login. Authenticated by a shared secret in X-Internal-Secret, never by a
 * session — there is no browser here and no cookie to carry.
 *
 * Why this exists at all: the game server cannot reach the database. OVH's
 * included databases only accept connections from inside their network, so a
 * server sitting in someone's house has no route to ucp_accounts. This file
 * runs where the database IS reachable, and answers the one question the game
 * server actually has — is this login good, and what rank is it — without the
 * game server ever holding database credentials.
 *
 * Deliberately mirrors login.php's gate rather than reimplementing it: same
 * lockout, same timing-safe miss, same status rules, same second factor via
 * twofa_check(). A player who cannot sign into the website must not be able
 * to sign into the city, and keeping both on one set of helpers is what stops
 * those two answers drifting apart.
 *
 * What it does NOT do, on purpose: no session is started, no remember-me
 * token is issued, and ucp_sessions is untouched. Signing into the game is
 * not signing into the website.
 */
require __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);

// ---- Shared secret -------------------------------------------------------
//
// Note there is no require_csrf() here, and that is correct: CSRF protects a
// browser that carries credentials automatically. This endpoint has no
// session and no cookies, so there is nothing for a third-party page to ride
// on — the secret below is the whole of the authentication.
$expected = (string)($CONFIG['game']['internal_secret'] ?? '');
$given    = (string)($_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '');

if ($expected === '') {
    error_log('UCP game-verify: game.internal_secret is not set in config.php');
    fail('Game integration is not configured.', 503);
}
// hash_equals, not === : a byte-by-byte comparison leaks the secret's prefix
// through timing to anyone who can make repeated calls.
if ($given === '' || !hash_equals($expected, $given)) {
    error_log('UCP game-verify: bad shared secret from ' . client_ip());
    fail('Forbidden.', 403);
}

// A wrong shared secret is the only thing rejected above. Everything past
// here is a genuine login attempt from the game server, so it is rate limited
// per calling IP as a backstop against that server being compromised and used
// to grind passwords.
throttle('game-verify', 60);

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));
$password = (string)($in['password'] ?? '');
$code     = trim((string)($in['code'] ?? ''));

if ($username === '' || $password === '') {
    fail('Username and password required.');
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, password_hash, admin_rank, status,
            totp_enabled, totp_secret, totp_last_step
       FROM ucp_accounts WHERE username_lower = ? LIMIT 1'
);
$stmt->execute([strtolower($username)]);
$acc = $stmt->fetch();

// ---- Lockout -------------------------------------------------------------
//
// Keyed on the GAME SERVER's IP, which is the same for every player on it.
// That is a deliberate trade: it means the game server shares one bucket per
// account rather than per player, so this throttles guessing against a single
// account without letting one player's fumbling lock out everyone else —
// each account still gets its own row.
$ip        = client_ip();
$accountId = $acc ? (int)$acc['id'] : null;
// Unknown names get their own bucket keyed on the name, so they throttle
// exactly like real ones and cannot be told apart by the response.
$probe = $acc ? '' : hash('sha256', strtolower($username));

$lockLeft = lock_seconds_left($pdo, $accountId, $ip, $probe);
if ($lockLeft > 0) {
    json_out([
        'ok'         => false,
        'error'      => 'Too many attempts. Try again shortly.',
        'locked'     => true,
        'locked_for' => $lockLeft,
    ], 429);
}

// Always run a real bcrypt verify, even when the account does not exist.
// Short-circuiting returns in microseconds where a real hash costs ~100ms, a
// gap wide enough to enumerate valid UCP names over the network. Same
// constant and same reasoning as login.php.
$dummyHash  = '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
$okPassword = password_verify($password, $acc['password_hash'] ?? $dummyHash);

if (!$acc || !$okPassword) {
    $lockedFor = record_failure($pdo, $accountId, $ip, $probe);

    // Only logged against a real account — a wrong guess at a name nobody
    // owns has no account to file it under.
    if ($accountId !== null) {
        require_once __DIR__ . '/_sessions.php';
        security_log($pdo, $accountId, 'signin_failed',
            'Wrong password (game server)' . ($lockedFor > 0 ? ' — locked for ' . $lockedFor . 's' : ''),
            'warn');
    }

    json_out([
        'ok'         => false,
        'error'      => 'Incorrect UCP name or password.',
        'locked'     => $lockedFor > 0,
        'locked_for' => $lockedFor,
    ], $lockedFor > 0 ? 429 : 401);
}

// ---- Status --------------------------------------------------------------
//
// Same rules as login.php, with the wording pointed at somewhere useful: a
// player stuck at a login screen in game cannot click a link, so each message
// names the site rather than assuming they can act on it here.
if ($acc['status'] === 'pending') {
    clear_failures($pdo, $accountId, $ip, $probe);
    fail('Verify your email at ucp.blaineside.com before connecting.', 403);
}
if ($acc['status'] === 'suspended') {
    clear_failures($pdo, $accountId, $ip, $probe);
    fail('This account is banned. Contact staff on Discord.', 403);
}
// A locked account is let into the WEBSITE so it can appeal (see _lock.php).
// There is no appeal route in game, so it is refused here and told where the
// route is — the opposite decision to login.php, for the same reason.
if ($acc['status'] === 'locked') {
    clear_failures($pdo, $accountId, $ip, $probe);
    fail('This account is locked. Sign in at ucp.blaineside.com to appeal.', 403);
}
if ($acc['status'] !== 'active') {
    clear_failures($pdo, $accountId, $ip, $probe);
    fail('This account cannot sign in. Contact staff on Discord.', 403);
}

// The password was right, so clear its counter before the code step —
// otherwise someone who genuinely has 2FA on but fumbles codes would rack up
// a password lockout too.
clear_failures($pdo, $accountId, $ip, $probe);

// ---- Second factor -------------------------------------------------------
//
// twofa_check() is the same call the website's 2fa-verify.php makes, so app
// codes, recovery codes, replay protection via totp_last_step and secret
// decryption all behave identically in both places.
if (!empty($acc['totp_enabled']) && !empty($acc['totp_secret'])) {
    if ($code === '') {
        json_out([
            'ok'            => false,
            'requires_totp' => true,
            'error'         => 'Enter the 6-digit code from your authenticator app.',
        ], 200);
    }

    $used = twofa_check($pdo, $acc, $code);
    if ($used === null) {
        json_out([
            'ok'            => false,
            'requires_totp' => true,
            'error'         => 'That code is not right. Try again.',
        ], 200);
    }

    if ($used === 'backup') {
        require_once __DIR__ . '/_sessions.php';
        security_log($pdo, (int)$acc['id'], 'twofa_backup_used',
            'Recovery code used to sign into the game server', 'warn');
    }
}

// ---- Success -------------------------------------------------------------
$rank = (int)$acc['admin_rank'];

// Recorded so "was that me?" covers the game as well as the website. This is
// the only write a successful game login makes to the account.
require_once __DIR__ . '/_sessions.php';
security_log($pdo, (int)$acc['id'], 'signin', 'Signed into the game server', 'info');

$pdo->prepare('UPDATE ucp_accounts SET last_login = NOW() WHERE id = ?')
    ->execute([(int)$acc['id']]);

ok([
    'account_id' => (int)$acc['id'],
    'username'   => $acc['username'],
    'admin_rank' => $rank,
    'rank_name'  => rank_name($rank),
]);
