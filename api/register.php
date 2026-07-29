<?php
/**
 * POST /api/register.php
 * Body: { username, email, discord?, password }
 *
 * Creates a PENDING UCP account, emails a verification link,
 * and — once email is verified — a matching IPS forum account is provisioned
 * via the IPS REST API (Option A) so the player's forum profile exists
 * before they ever log in through OAuth.
 *
 * Note: The IPS account creation is also attempted here at registration time.
 * If it fails (IPS offline, config missing) registration still succeeds —
 * the forum account will be created on first OAuth login instead.
 */

declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('register', 6);

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));
$email    = trim((string)($in['email']    ?? ''));
$discord  = trim((string)($in['discord']  ?? ''));
$password = (string)($in['password']      ?? '');

// ---- Validation ----
if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
    fail('Username must be 3–20 characters: letters, numbers, underscores.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fail('Please enter a valid email address.');
}
if ($discord !== '' && !preg_match('/^[a-z0-9._]{2,32}$/', $discord)) {
    fail('That Discord username doesn\'t look right.');
}
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail('Password needs 8+ characters, an uppercase letter and a number.');
}

// Block disposable emails
$domain     = strtolower(substr(strrchr($email, '@') ?: '', 1));
$disposable = [
    'mailinator.com','guerrillamail.com','10minutemail.com','tempmail.com',
    'yopmail.com','trashmail.com','sharklasers.com','getnada.com','dispostable.com',
    'fakeinbox.com','mohmal.com','emailondeck.com','moakt.com','throwawaymail.com',
];
if (in_array($domain, $disposable, true)) {
    fail('Please use a permanent email address, not a disposable one.');
}

$pdo = db();

// ---- Uniqueness checks ----
$stmt = $pdo->prepare('SELECT id FROM ucp_accounts WHERE username_lower = ? LIMIT 1');
$stmt->execute([strtolower($username)]);
if ($stmt->fetch()) fail('That username is already taken.');

$stmt = $pdo->prepare('SELECT id FROM ucp_accounts WHERE email_lower = ? LIMIT 1');
$stmt->execute([strtolower($email)]);
if ($stmt->fetch()) fail('An account with that email already exists.');

// ---- Create UCP account ----
$hash  = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare(
    'INSERT INTO ucp_accounts
       (username, username_lower, email, email_lower, discord, password_hash, status, verify_token, verify_expires, created_at)
     VALUES (?, ?, ?, ?, ?, ?, \'pending\', ?, ?, NOW())'
);
$stmt->execute([
    $username, strtolower($username),
    $email,    strtolower($email),
    $discord !== '' ? $discord : null,
    $hash, $token, time() + 172800,   // link valid 48 hours
]);
$accountId = (int)$pdo->lastInsertId();

// ---- Option A: Provision IPS forum account via REST API ----
// This runs fire-and-forget; a failure does NOT block registration.
// The forum account will also be created on first OAuth login if missing.
$forumMemberId = ips_provision_member($username, $email, $CONFIG);
if ($forumMemberId) {
    $pdo->prepare('UPDATE ucp_accounts SET forum_member_id = ? WHERE id = ?')
        ->execute([$forumMemberId, $accountId]);

    // Push the UCP name into the forum profile ("UCP Name" field) immediately.
    // Fire-and-forget; the hourly cron + IPS webhook act as fallbacks.
    $sync = curl_init('https://blaineside.com/ucp-name-sync.php?key=bs-sync-9f2k7');
    curl_setopt_array($sync, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);
    curl_exec($sync);
    curl_close($sync);
}

// ---- Send verification email ----
$link = rtrim($CONFIG['site']['base_url'], '/') . '/api/verify.php?token=' . urlencode($token);
$res  = send_mail(
    $email, $username,
    'Verify your BlaineSide UCP account',
    verification_email_html($username, $link),
    "Hi $username, verify your BlaineSide account: $link"
);

if (!$res['ok']) {
    ok([
        'id'         => $accountId,
        'email'      => $email,
        'email_sent' => false,
        'message'    => 'Account created, but we could not send the email. Use "Resend email".',
    ]);
}

ok([
    'id'         => $accountId,
    'email'      => $email,
    'email_sent' => true,
    'message'    => 'Account created. Check your email to verify.',
]);

// ============================================================
// IPS REST API provisioning helper (Option A)
// ============================================================
/**
 * Creates an IPS member via the REST API.
 * Returns the new IPS member ID on success, or null on failure.
 *
 * IPS API docs: https://invisioncommunity.com/developers/rest-api
 * Endpoint: POST /api/core/members
 * Auth: HTTP Basic — username = API key, password = empty
 */
function ips_provision_member(string $username, string $email, array $config): ?int {
    // Config check — skip silently if not configured
    $ips = $config['ips'] ?? [];
    if (empty($ips['api_url']) || empty($ips['api_key'])) return null;

    $apiUrl = rtrim($ips['api_url'], '/') . '/core/members';

    // IPS requires a password on account creation even for OAuth-only accounts.
    // We set a long random password — the player will never use it (OAuth is the gate).
    $dummyPassword = bin2hex(random_bytes(24));

    $payload = http_build_query([
        'name'     => $username,
        'email'    => $email,
        'password' => $dummyPassword,
        // Validated = 1 so the account is immediately active (UCP already verified email)
        'validated' => 1,
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_USERPWD        => $ips['api_key'] . ':',   // Basic auth: key as username, empty pw
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code < 200 || $code >= 300) return null;

    $data = json_decode($raw, true);
    $id   = $data['id'] ?? null;
    return $id ? (int)$id : null;
}
