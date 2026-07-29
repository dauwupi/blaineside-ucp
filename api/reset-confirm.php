<?php
/**
 * POST /api/reset-confirm.php
 * Body: { token, password }
 *
 * Completes the password reset. Validates the single-use token, re-checks the
 * password rules server-side (the client checks are only a hint), writes the
 * new hash, then invalidates the token AND every existing session for that
 * account (remember-me tokens included) so a stolen session can't survive a
 * password change.
 */

declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('reset_confirm', 8);

$in       = read_input();
$token    = (string)($in['token'] ?? '');
$password = (string)($in['password'] ?? '');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    fail('This reset link is invalid or incomplete. Request a new one.', 400);
}

// Same rules as register.php — keep the two in sync.
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    fail('Password needs 8+ characters, an uppercase letter and a number.');
}
if (strlen($password) > 200) {
    fail('That password is too long.');
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, reset_expires FROM ucp_accounts
      WHERE reset_token = ? AND status = \'active\' LIMIT 1'
);
$stmt->execute([token_hash($token)]);
$acc = $stmt->fetch();

if (!$acc) {
    fail('This reset link has already been used, or it is not valid. Request a new one.', 400);
}
if ((int)$acc['reset_expires'] < time()) {
    // Expired — clear it so it can't be probed further.
    $pdo->prepare('UPDATE ucp_accounts SET reset_token = NULL, reset_expires = NULL WHERE id = ?')
        ->execute([(int)$acc['id']]);
    fail('This reset link has expired. Reset links are valid for 30 minutes — request a new one.', 400);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// New hash + burn the token + drop all remember-me sessions for this account.
$pdo->prepare(
    'UPDATE ucp_accounts
        SET password_hash   = ?,
            reset_token     = NULL,
            reset_expires   = NULL,
            remember_token  = NULL,
            remember_expires = NULL,
            session_epoch   = session_epoch + 1
      WHERE id = ?'
)->execute([$hash, (int)$acc['id']]);

// Any failed-login lockout for this account is cleared — they proved ownership.
$pdo->prepare('DELETE FROM ucp_login_attempts WHERE account_id = ?')->execute([(int)$acc['id']]);

// Bumping session_epoch above is what actually ends sessions on OTHER
// devices. This file's docblock always claimed it did that, but it only ever
// destroyed the session of the browser doing the reset — so someone holding a
// stolen session cookie stayed signed in after the victim changed their
// password, which is the one moment a password change most needs to work.
// session.php now compares the epoch on every request.
if (!empty($_SESSION['uid']) && (int)$_SESSION['uid'] === (int)$acc['id']) {
    $_SESSION = [];
    session_destroy();
}

ok(['message' => 'Password updated. You can sign in with your new password.']);
