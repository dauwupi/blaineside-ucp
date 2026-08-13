<?php
/**
 * POST /api/settings-delete.php
 * Body: { password }
 *
 * Deletes the UCP.
 *
 * OFF BY DEFAULT. The rule is that a player can't delete their UCP while
 * anything sits on their administrative record — otherwise anyone facing a ban
 * wipes their history and comes back clean. That record system doesn't exist
 * yet, so there is nothing to check against and the endpoint refuses outright.
 *
 * Set security.allow_self_delete = true in config.php once the punishment
 * tables are live AND the check below is pointed at them.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('settings_delete', 4);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

if (empty($CONFIG['security']['allow_self_delete'])) {
    json_out([
        'ok'        => false,
        'available' => false,
        'error'     => 'Deleting your UCP isn\'t available yet. Open a ticket on Discord and '
                     . 'staff will do it for you.',
    ], 409);
}

require_password($pdo, $acc, (string)(read_input()['password'] ?? ''), 'settings_delete');

// ---- The administrative-record gate ----------------------------------------
// Left as an explicit failure rather than a silent pass: if someone turns the
// config flag on before the punishment tables exist, deletion must refuse, not
// quietly let a banned player erase themselves.
$hasRecord = null;
try {
    $st = $pdo->query("SHOW TABLES LIKE 'ucp_punishments'");
    if ($st && $st->fetch()) {
        $q = $pdo->prepare('SELECT COUNT(*) FROM ucp_punishments WHERE uid = ?');
        $q->execute([$uid]);
        $hasRecord = (int)$q->fetchColumn() > 0;
    }
} catch (Throwable $e) {
    error_log('UCP delete: record check failed for #' . $uid . ': ' . $e->getMessage());
}

if ($hasRecord === null) {
    json_out([
        'ok'    => false,
        'error' => 'Account deletion is switched on but the administrative record can\'t be '
                 . 'checked, so we can\'t confirm your record is clear. Contact a Founder.',
    ], 503);
}
if ($hasRecord) {
    json_out([
        'ok'      => false,
        'blocked' => true,
        'error'   => 'You can\'t delete your UCP while you have anything on your administrative '
                   . 'record. Appeal an entry from Reports & Appeals if you believe it is wrong.',
    ], 403);
}

// ---- Delete -----------------------------------------------------------------
$pdo->beginTransaction();
try {
    $pdo->prepare('DELETE FROM ucp_2fa_backup_codes WHERE uid = ?')->execute([$uid]);
    $pdo->prepare('DELETE FROM ucp_login_attempts WHERE account_id = ?')->execute([$uid]);
    $pdo->prepare('DELETE FROM ucp_accounts WHERE id = ?')->execute([$uid]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('UCP delete failed for #' . $uid . ': ' . $e->getMessage());
    fail('Could not delete the account. Nothing has been changed — contact staff.', 500);
}

// The forum account is left for staff: IPS owns the posts, and deleting a
// member there is a separate decision about whether their posts go with them.
error_log('UCP account #' . $uid . ' (' . $acc['username'] . ') deleted by the account holder'
        . ($acc['forum_member_id'] !== null
           ? '; forum member ' . (int)$acc['forum_member_id'] . ' still exists'
           : ''));

$_SESSION = [];
session_destroy();
setcookie('bsucp_rm', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true,
                           'samesite' => 'Lax', 'secure' => is_https()]);

ok(['deleted' => true, 'redirect' => '/login?deleted=1']);
