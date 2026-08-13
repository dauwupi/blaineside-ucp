<?php
/**
 * POST /api/2fa-disable.php
 * Body: { password, code }
 *
 * Turns two-factor off. Requires BOTH factors — the account password and a
 * current authenticator or recovery code — because anything less means a
 * stolen session alone can undo it, and the second factor exists precisely to
 * survive a stolen session.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('2fa_disable', 10);

$pdo = db();
$acc = twofa_current_account($pdo);
$uid = (int)$acc['id'];
$ip  = client_ip();

if (empty($acc['totp_enabled'])) {
    fail('Two-factor authentication is not currently on for this account.', 409);
}

// Staff whose rank requires 2FA cannot switch it off; they need a rank change
// or a config change first. Better to say so than to let them disable it and
// be bounced straight back to the setup screen on their next sign-in.
if (twofa_is_required((int)$acc['admin_rank'])) {
    fail('Two-factor authentication is required for your staff rank and cannot be switched off. Contact a Founder if you need it moved to a new device.', 403);
}

$in = read_input();
twofa_require_password($pdo, $acc, (string)($in['password'] ?? ''));

$lockLeft = lock_seconds_left($pdo, $uid, $ip, '2fa_settings');
if ($lockLeft > 0) {
    json_out(['ok' => false, 'error' => 'Too many attempts. Try again shortly.',
              'locked' => true, 'locked_for' => $lockLeft], 429);
}

$code = trim((string)($in['code'] ?? ''));
if ($code === '') {
    fail('Enter a code from your authenticator app, or one of your recovery codes.');
}

if (twofa_check($pdo, $acc, $code) === null) {
    $lockedFor = record_failure($pdo, $uid, $ip, '2fa_settings');
    json_out([
        'ok'         => false,
        'field'      => 'code',
        'error'      => 'That code is not right.',
        'locked'     => $lockedFor > 0,
        'locked_for' => $lockedFor,
    ], $lockedFor > 0 ? 429 : 401);
}

clear_failures($pdo, $uid, $ip, '2fa_settings');

$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET totp_secret = NULL, totp_enabled = 0,
                totp_last_step = 0, totp_enabled_at = NULL
          WHERE id = ?'
    )->execute([$uid]);
    // Recovery codes are only meaningful alongside a secret; leaving them
    // behind would let an old printout work again if 2FA is ever re-enabled.
    $pdo->prepare('DELETE FROM ucp_2fa_backup_codes WHERE uid = ?')->execute([$uid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

require_once __DIR__ . '/_sessions.php';
security_log($pdo, $uid, '2fa_disabled',
    'Recovery codes deleted', 'warn');

ok([
    'enabled' => false,
    'message' => 'Two-factor authentication is off. Your recovery codes have been deleted.',
]);
