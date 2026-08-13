<?php
/**
 * POST /api/session-revoke.php
 * Body: { id }
 *
 * Ends one signed-in device from "Where you're signed in".
 *
 * No password is asked for, deliberately. Signing a device out is the safe
 * direction — the worst case is inconvenience, and the person doing it is
 * usually reacting to something they don't recognise. Putting a password
 * prompt in the way of that is how people end up not bothering. CSRF still
 * applies, so another site can't do it on their behalf.
 *
 * The session id is only ever accepted if it belongs to the caller's own
 * account, and an id belonging to someone else is answered exactly like one
 * that doesn't exist.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('session_revoke', 30);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

$in  = read_input();
$sid = trim((string)($in['id'] ?? ''));

if (!preg_match('/^[a-f0-9]{32}$/', $sid)) {
    fail('That session could not be found.', 404);
}

// Ending the session you are reading this on is just logging out, and doing
// it here would leave the page sitting on a dead session without knowing.
if ($sid === (string)($_SESSION['sid'] ?? '')) {
    json_out(['ok' => false, 'error' => 'That is this device. Use Log out instead.'], 400);
}

$device = session_revoke_one($pdo, $uid, $sid);
if ($device === null) {
    fail('That session could not be found.', 404);
}

security_log($pdo, $uid, 'session_revoked', $device, 'good');

ok([
    'device'  => $device,
    'message' => $device . ' has been signed out.',
]);
