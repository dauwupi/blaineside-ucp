<?php
/**
 * POST /api/settings-email.php
 * Body: { password, email }
 *
 * Starts an email change. The address is NOT changed here — it is parked in
 * pending_email and a link goes to the new address. The current address keeps
 * working until that link is opened, so a typo can't lock anyone out of their
 * own account.
 *
 * A heads-up also goes to the CURRENT address. That is the part that matters:
 * if someone else is moving your account to their inbox, the notice lands
 * somewhere you can still read.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('settings_email', 5);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

$in    = read_input();
$email = trim((string)($in['email'] ?? ''));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    json_out(['ok' => false, 'field' => 'email',
              'error' => 'That doesn\'t look like an email address.'], 400);
}
if (strcasecmp($email, (string)$acc['email']) === 0) {
    json_out(['ok' => false, 'field' => 'email',
              'error' => 'That is already your address.'], 400);
}

// Same disposable-domain list as register.php — an address that can't receive
// a reset link in six months is not one to move an account to.
$domain     = strtolower(substr(strrchr($email, '@') ?: '', 1));
$disposable = [
    'mailinator.com','guerrillamail.com','10minutemail.com','tempmail.com',
    'yopmail.com','trashmail.com','sharklasers.com','getnada.com','dispostable.com',
    'fakeinbox.com','mohmal.com','emailondeck.com','moakt.com','throwawaymail.com',
];
if (in_array($domain, $disposable, true)) {
    json_out(['ok' => false, 'field' => 'email',
              'error' => 'Please use a permanent email address, not a disposable one.'], 400);
}

require_password($pdo, $acc, (string)($in['password'] ?? ''), 'settings_email');

// Collision is only reported HERE, behind a password and a CSRF token, never
// from the live check on the form — see the comment in api/check.php.
$st = $pdo->prepare('SELECT id FROM ucp_accounts WHERE email_lower = ? AND id <> ? LIMIT 1');
$st->execute([strtolower($email), $uid]);
if ($st->fetch()) {
    json_out(['ok' => false, 'field' => 'email',
              'error' => 'That address is already used by another account.'], 409);
}

$token   = bin2hex(random_bytes(32));
$expires = time() + 7200;   // 2 hours — long enough to find the mail, short enough to matter

$pdo->prepare(
    'UPDATE ucp_accounts
        SET pending_email = ?, pending_email_token = ?, pending_email_expires = ?
      WHERE id = ?'
)->execute([$email, token_hash($token), $expires, $uid]);

$link = rtrim($CONFIG['site']['base_url'], '/')
      . '/api/settings-email-confirm.php?token=' . urlencode($token);

// ---- To the new address: the link ------------------------------------------
$sent = send_mail(
    $email, (string)$acc['username'],
    'Confirm your new BlaineSide UCP email',
    email_change_email_html((string)$acc['username'], $link),
    "Hi {$acc['username']}, confirm your new BlaineSide email address (valid 2 hours): $link"
);

// ---- To the old address: a warning, with no link to click -------------------
// Deliberately actionless. If this wasn't you, the instruction is to change
// your password, which invalidates the pending change along with everything else.
send_mail(
    (string)$acc['email'], (string)$acc['username'],
    'Someone asked to change your BlaineSide UCP email',
    email_change_notice_html((string)$acc['username'], mask_email($email)),
    "Hi {$acc['username']}, a change of email to " . mask_email($email) .
    " was requested on your BlaineSide UCP. If that wasn't you, change your password immediately."
);

if (!$sent['ok']) {
    error_log('UCP email change: send failed for #' . $uid . ': ' . ($sent['error'] ?? ''));
    json_out(['ok' => false, 'field' => 'email',
              'error' => 'We couldn\'t send the confirmation email. Try again in a minute.'], 502);
}

ok([
    'pending' => mask_email($email),
    'expires' => $expires,
    'message' => 'Check ' . $email . ' for the confirmation link. Your current address keeps working until you open it.',
]);
