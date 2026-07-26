<?php
/**
 * POST /api/register.php
 * Body: { username, email, discord?, password }
 * Creates a PENDING account, emails a verification link.
 * Returns the new Account ID on success.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
throttle('register', 6);

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));
$email    = trim((string)($in['email'] ?? ''));
$discord  = trim((string)($in['discord'] ?? ''));
$password = (string)($in['password'] ?? '');

// ---- Validation (mirrors the front-end rules) ----
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

// Block obvious disposable email domains.
$domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
$disposable = ['mailinator.com','guerrillamail.com','10minutemail.com','tempmail.com',
    'yopmail.com','trashmail.com','sharklasers.com','getnada.com','dispostable.com',
    'fakeinbox.com','mohmal.com','emailondeck.com','moakt.com','throwawaymail.com'];
if (in_array($domain, $disposable, true)) {
    fail('Please use a permanent email address, not a disposable one.');
}

$pdo = db();

// ---- Uniqueness checks (case-insensitive) ----
$stmt = $pdo->prepare('SELECT id FROM ucp_accounts WHERE username_lower = ? LIMIT 1');
$stmt->execute([strtolower($username)]);
if ($stmt->fetch()) fail('That username is already taken.');

$stmt = $pdo->prepare('SELECT id, status FROM ucp_accounts WHERE email_lower = ? LIMIT 1');
$stmt->execute([strtolower($email)]);
if ($stmt->fetch()) fail('An account with that email already exists.');

// ---- Create the account ----
$hash  = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));   // 64-char verification token

$stmt = $pdo->prepare(
    'INSERT INTO ucp_accounts
       (username, username_lower, email, email_lower, discord, password_hash, status, verify_token, created_at)
     VALUES (?, ?, ?, ?, ?, ?, "pending", ?, NOW())'
);
$stmt->execute([
    $username, strtolower($username),
    $email, strtolower($email),
    $discord !== '' ? $discord : null,
    $hash, $token,
]);

$accountId = (int)$pdo->lastInsertId();   // ← the unique Account ID

// ---- Send verification email ----
$link = rtrim($CONFIG['site']['base_url'], '/') . '/api/verify.php?token=' . urlencode($token);
$res  = send_mail(
    $email, $username,
    'Verify your BlaineSide UCP account',
    verification_email_html($username, $link),
    "Hi $username, verify your BlaineSide account: $link"
);

if (!$res['ok']) {
    // Account exists but the email failed — let them resend rather than blocking.
    ok([
        'id' => $accountId,
        'email' => $email,
        'email_sent' => false,
        'message' => 'Account created, but we could not send the email. Use “Resend email”.',
    ]);
}

ok([
    'id' => $accountId,
    'email' => $email,
    'email_sent' => true,
    'message' => 'Account created. Check your email to verify.',
]);
