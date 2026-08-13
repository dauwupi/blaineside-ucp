<?php
/**
 * POST /api/2fa-codes.php
 * Body: { password, code }
 *
 * Issues a fresh set of recovery codes and revokes the old ones. For when the
 * printout is lost, or when the "2 codes left" warning finally lands.
 *
 * Same two-factor requirement as disabling: a stolen session must not be able
 * to mint itself a permanent way back in.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('2fa_codes', 6);

$pdo = db();
$acc = twofa_current_account($pdo);
$uid = (int)$acc['id'];
$ip  = client_ip();

if (empty($acc['totp_enabled'])) {
    fail('Turn on two-factor authentication first.', 409);
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
    fail('Enter a code from your authenticator app.');
}

// A recovery code is accepted here too — someone who has lost their phone but
// still holds one code can use it to get a fresh sheet, then disable and
// re-enable 2FA on the new device.
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

$codes = twofa_issue_backup_codes($pdo, $uid);

ok([
    'backup_codes' => $codes,
    'backup_total' => BS_BACKUP_CODE_COUNT,
    'message'      => 'New recovery codes issued. Your previous codes no longer work.',
]);
