<?php
/**
 * POST /api/settings-password.php
 * Body: { current, password }
 *
 * Changes the password from inside the UCP. Same rules as register.php and
 * reset-confirm.php — keep the three in sync.
 *
 * Signs out every other device by bumping session_epoch, then re-stamps this
 * session so the person doing the changing stays where they are. Any pending
 * email change is cancelled in the same statement: if the password is being
 * changed because someone else got in, the half-finished move to their inbox
 * must not survive it.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('settings_password', 8);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

$in       = read_input();
$password = (string)($in['password'] ?? '');

// Same rules as register.php — keep them in step.
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    json_out(['ok' => false, 'field' => 'password',
              'error' => 'Password needs 8+ characters, an uppercase letter and a number.'], 400);
}
if (strlen($password) > 200) {
    json_out(['ok' => false, 'field' => 'password',
              'error' => 'That password is too long.'], 400);
}

require_password($pdo, $acc, (string)($in['current'] ?? ''), 'settings_password');

if (password_verify($password, (string)$acc['password_hash'])) {
    json_out(['ok' => false, 'field' => 'password',
              'error' => 'That is already your password.'], 400);
}

$pdo->prepare(
    'UPDATE ucp_accounts
        SET password_hash         = ?,
            password_changed_at   = ?,
            reset_token           = NULL,
            reset_expires         = NULL,
            pending_email         = NULL,
            pending_email_token   = NULL,
            pending_email_expires = NULL
      WHERE id = ?'
)->execute([password_hash($password, PASSWORD_DEFAULT), time(), $uid]);

// Ends every other session, clears remember-me, keeps this one.
sign_out_other_devices($pdo, $uid);
session_regenerate_id(true);

// Any lockout from fumbling the old password is cleared — they proved ownership.
$pdo->prepare('DELETE FROM ucp_login_attempts WHERE account_id = ?')->execute([$uid]);

send_mail(
    (string)$acc['email'], (string)$acc['username'],
    'Your BlaineSide UCP password was changed',
    password_changed_email_html((string)$acc['username']),
    "Hi {$acc['username']}, your BlaineSide UCP password was just changed and all other devices " .
    "were signed out. If this wasn't you, reset it from the sign-in page immediately."
);

ok(['message' => 'Password updated. Every other device has been signed out.']);
