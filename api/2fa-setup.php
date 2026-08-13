<?php
/**
 * POST /api/2fa-setup.php
 * Body: { password }
 *
 * Step one of turning two-factor on. Mints a secret, keeps it in the session
 * only, and hands back the otpauth:// URI so the page can draw a QR code.
 *
 * Nothing is written to the account here. If the user closes the tab now,
 * their sign-in is untouched — the secret is only committed by
 * 2fa-confirm.php, once they have proved the app is actually generating
 * matching codes. Writing it up front is how people lock themselves out.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('2fa_setup', 10);

$pdo = db();
$acc = twofa_current_account($pdo);

$in = read_input();
twofa_require_password($pdo, $acc, (string)($in['password'] ?? ''));

if (!empty($acc['totp_enabled'])) {
    fail('Two-factor authentication is already on for this account. Turn it off first if you want to move it to a new device.', 409);
}

$secret = Totp::generateSecret();

$_SESSION['totp_pending_secret'] = $secret;
$_SESSION['totp_pending_exp']    = time() + 900;   // 15 minutes to finish setup

// The account name in the URI is what shows under the entry in the app, so
// use the UCP name rather than the email — it is what people recognise.
$uri = Totp::uri($secret, (string)$acc['username'], twofa_issuer());

ok([
    'secret'        => $secret,                  // for manual entry
    'secret_pretty' => Totp::pretty($secret),
    'uri'           => $uri,                     // the page renders this as a QR
    'issuer'        => twofa_issuer(),
    'account'       => $acc['username'],
    'expires_in'    => 900,
]);
