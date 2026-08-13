<?php
/**
 * POST /api/settings-signout.php
 * Body: { password }
 *
 * "Sign out everywhere". Ends every session except this one and drops the
 * remember-me token, so a device you no longer have stops being able to walk
 * back in. Asks for the password because a session left open on a shared
 * machine shouldn't be able to lock the real owner out of their other ones.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('settings_signout', 6);

$pdo = db();
$acc = current_account($pdo);

require_password($pdo, $acc, (string)(read_input()['password'] ?? ''), 'settings_signout');

sign_out_other_devices($pdo, (int)$acc['id']);
session_regenerate_id(true);

ok(['message' => 'Signed out on every other device. You are still signed in here.']);
