<?php
/**
 * POST /api/punishment-delete.php
 * Body: { id, why?: "..." }
 *
 * Removes one entry from somebody's administrative record completely.
 *
 * Management and the Founder only — not the administrator who issued it.
 * Deleting is the power to make a punishment never have happened, and an
 * administrator who can erase their own entries can erase the evidence of a
 * bad one before anybody reads it. That is the whole reason the bar for this
 * is higher than the bar for issuing.
 *
 * "Completely" means completely as far as the record is concerned: the
 * player's Standing tab, the staff view, the tally and the filters all stop
 * counting it, and nothing on either page says an entry used to be there.
 * A row does stay in ucp_punishment_log with the full entry inside it, so a
 * Founder can answer "who deleted what" months later. Those two are not in
 * conflict — the record is what the account is judged on, the log is what
 * the staff team is judged on.
 *
 * If the punishment is still in force it is lifted first, and a user lock is
 * released with it. Deleting the row without that would leave somebody
 * locked out of an account with nothing on file saying why.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/_punish.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('punish_delete', 20);

$pdo = db();
$acc = current_account($pdo);

if (!record_may_delete($acc)) {
    json_out(['ok' => false,
              'error' => 'Deleting an entry from a record is for ' . rank_name(BS_RECORD_ADMIN_RANK)
                       . ' and the Founder. Everyone else can only correct the reason on an '
                       . 'entry they issued themselves.'], 403);
}
if (!punish_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'The punishment tables aren\'t on this server yet — '
                       . 'docs/migration-appeals.sql hasn\'t been run.'], 409);
}
if (!punish_edit_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'Deleting an entry needs docs/migration-record-edit.sql to have been '
                       . 'run — there would be no record of the deletion itself.'], 409);
}

$in  = read_input();
$id  = (int)($in['id'] ?? 0);
$why = trim((string)($in['why'] ?? ''));

$p = punish_by_id($pdo, $id);
if (!$p) fail('That entry is no longer on the record.', 404);

if ((int)$p['account_id'] === (int)$acc['id']) {
    json_out(['ok' => false,
              'error' => 'You can\'t delete an entry from your own record.'], 403);
}

$accountId = (int)$p['account_id'];
$wasInForce = punish_in_force($p);

/* The log row goes in FIRST, with the whole entry inside it. If the delete
   below fails the log has an orphan; if the order were reversed a failure
   would lose the entry and the note of who removed it together. */
punish_log_add($pdo, $p, $acc, 'deleted',
    $why !== '' ? $why : 'No reason given',
    json_encode([
        'kind'       => (string)$p['kind'],
        'reason'     => $p['reason'],
        'permanent'  => !empty($p['permanent']),
        'expires_at' => $p['expires_at'],
        'issued_at'  => (int)$p['issued_at'],
        'issued_by'  => $p['issued_by_name'],
        'active'     => $wasInForce,
    ], JSON_UNESCAPED_UNICODE));

/* A lock has to come off before the row that explains it disappears. */
$unlocked = false;
if ($wasInForce && (string)$p['kind'] === 'user_lock') {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET status = \'active\', locked_at = NULL, locked_by = NULL,
                locked_by_name = NULL, lock_reason = NULL
          WHERE id = ? AND status = \'locked\''
    )->execute([$accountId]);
    security_log($pdo, $accountId, 'account_unlocked',
        'Lock removed by ' . $acc['username'] . ' — the entry was deleted from the record', 'good');
    $unlocked = true;
}

/* Appeals against it lose the attachment, not their existence. The appeal was
   still made and still answered; it just no longer points at a row that is
   gone, and appeal_punishments() already draws an appeal with none.

   The foreign key on that table is ON DELETE CASCADE, so MySQL would do this
   on the next statement anyway. It is written out because relying on a
   cascade to clear a join row is the kind of thing that is true until
   somebody rebuilds the table without the constraint. */
try {
    $pdo->prepare('DELETE FROM ucp_appeal_punishments WHERE punishment_id = ?')->execute([$id]);
} catch (Throwable $e) {
    // Appeals not migrated on this server. Nothing to detach.
}

$pdo->prepare('DELETE FROM ucp_punishments WHERE id = ?')->execute([$id]);

ok([
    'id'       => $id,
    'unlocked' => $unlocked,
    'message'  => 'Entry deleted from the record.'
                . ($unlocked ? ' The user lock was removed with it.' : '')
                . ' The deletion itself is logged.',
]);
