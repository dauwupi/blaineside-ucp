<?php
/**
 * POST /api/2fa-confirm.php
 * Body: { code }
 *
 * Step two of turning two-factor on. Checks a code against the secret held in
 * the session by 2fa-setup.php, and only if it matches does the secret reach
 * the database. Returns the recovery codes — the one and only time they are
 * readable.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('2fa_confirm', 12);

$pdo = db();
$acc = twofa_current_account($pdo);
$uid = (int)$acc['id'];

if (!empty($acc['totp_enabled'])) {
    fail('Two-factor authentication is already on for this account.', 409);
}

$secret = (string)($_SESSION['totp_pending_secret'] ?? '');
$exp    = (int)($_SESSION['totp_pending_exp'] ?? 0);

if ($secret === '' || time() > $exp) {
    unset($_SESSION['totp_pending_secret'], $_SESSION['totp_pending_exp']);
    json_out(['ok' => false, 'restart' => true,
              'error' => 'This setup has expired. Start again to get a fresh QR code.'], 400);
}

$in   = read_input();
$code = preg_replace('/\D+/', '', (string)($in['code'] ?? '')) ?? '';

if (strlen($code) !== 6) {
    fail('Enter the 6-digit code shown in your authenticator app.');
}

// afterStep 0: nothing has been used against this secret yet.
$step = Totp::verify($secret, $code, 1, 0);

if ($step === null) {
    $tries = (int)($_SESSION['totp_pending_tries'] ?? 0) + 1;
    $_SESSION['totp_pending_tries'] = $tries;

    // Ten wrong codes means the app is set up against a different secret (or
    // the phone's clock is wrong) — throw the pending secret away rather than
    // letting them grind, and make them rescan.
    if ($tries >= 10) {
        unset($_SESSION['totp_pending_secret'], $_SESSION['totp_pending_exp'], $_SESSION['totp_pending_tries']);
        json_out(['ok' => false, 'restart' => true,
                  'error' => 'Too many incorrect codes. Start the setup again.'], 429);
    }

    fail('That code did not match. Codes change every 30 seconds — try the current one. If it keeps failing, check the date and time settings on your phone are set to automatic.', 401);
}

// Committed together: an enabled flag without a secret, or a secret the
// verifier hasn't seen a matching code for, both lock the account out.
$pdo->beginTransaction();
try {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET totp_secret    = ?,
                totp_enabled   = 1,
                totp_last_step = ?,
                totp_enabled_at = NOW(),
                -- Every "remember me" device is revoked. _bootstrap.php
                -- restores a session straight from that token without ever
                -- reaching login.php, so a phone or laptop that was trusted
                -- BEFORE 2FA existed would keep walking straight in — the one
                -- device you would most want the new second factor to cover.
                remember_token   = NULL,
                remember_expires = NULL
          WHERE id = ?'
    )->execute([twofa_encrypt_secret($secret), $step, $uid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

// Issued after the commit: if code generation failed we would rather the user
// retry than end up with 2FA on and no way back in.
$codes = twofa_issue_backup_codes($pdo, $uid);

unset($_SESSION['totp_pending_secret'], $_SESSION['totp_pending_exp'], $_SESSION['totp_pending_tries']);

// This browser's own remember-me cookie was just invalidated along with the
// rest. Clear it so the bootstrap doesn't spend a DB lookup on it, and so the
// user isn't left holding a token that silently means nothing.
$_SESSION['remember'] = false;
setcookie('bsucp_rm', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => is_https(),
]);

require_once __DIR__ . '/_sessions.php';
security_log($pdo, $uid, '2fa_enabled',
    BS_BACKUP_CODE_COUNT . ' recovery codes issued', 'good');

ok([
    'enabled'       => true,
    'backup_codes'  => $codes,
    'backup_total'  => BS_BACKUP_CODE_COUNT,
    'message'       => 'Two-factor authentication is on. Save your recovery codes now — they are not shown again.',
]);
