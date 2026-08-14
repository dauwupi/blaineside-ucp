<?php
/**
 * POST /api/punishment-edit.php
 * Body: { id, reason: "..." }
 *
 * Corrects the reason written on one entry of somebody's administrative
 * record.
 *
 * Only the reason. Not the kind, not the length, not who it was issued
 * against and not the date — those are what the punishment IS, and changing
 * them after the fact would make the record say a different thing happened.
 * If the punishment itself was wrong, it gets lifted or deleted; it does not
 * get quietly rewritten into a different punishment.
 *
 * Who: the administrator who issued it, on their own entry, plus Management
 * and the Founder on anything. See record_may_edit() in _punish.php for why
 * those two are the same power and not one.
 *
 * The previous wording is kept in ucp_punishment_log. An edit that leaves no
 * trace of what it replaced is indistinguishable from the entry always
 * having said that, which is exactly the doubt a record exists to remove.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/_punish.php';

const BS_PUNISH_REASON_MAX = 400;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('punish_edit', 30);

$pdo = db();
$acc = current_account($pdo);

if (!admin_may_search((int)$acc['admin_rank'])) {
    json_out(['ok' => false,
              'error' => 'The administrative record is for ' . rank_name(BS_ADMIN_MIN_RANK)
                       . ' and above.'], 403);
}
if (!punish_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'The punishment tables aren\'t on this server yet — '
                       . 'docs/migration-appeals.sql hasn\'t been run.'], 409);
}
if (!punish_edit_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'Editing a record entry needs docs/migration-record-edit.sql to have '
                       . 'been run — there is nowhere to record who changed it.'], 409);
}

$in     = read_input();
$id     = (int)($in['id'] ?? 0);
$reason = trim((string)($in['reason'] ?? ''));

$p = punish_by_id($pdo, $id);
if (!$p) fail('That entry is no longer on the record.', 404);

if (!record_may_edit($acc, $p)) {
    json_out(['ok' => false,
              'error' => 'You can only change the reason on a punishment you issued yourself. '
                       . 'Management and the Founder can change any of them.'], 403);
}
if ((int)$p['account_id'] === (int)$acc['id']) {
    json_out(['ok' => false,
              'error' => 'You can\'t edit an entry on your own record.'], 403);
}

if ($reason === '') {
    fail('Write the reason. An entry with no reason on it is worse than a badly worded one.', 422);
}
if (mb_strlen($reason) > BS_PUNISH_REASON_MAX) {
    fail('That reason is too long — keep it under ' . BS_PUNISH_REASON_MAX . ' characters.', 422);
}

$was = (string)($p['reason'] ?? '');
if ($was === $reason) {
    ok(['id' => $id, 'reason' => $reason, 'message' => 'Nothing changed.']);
}

$now = time();
$pdo->prepare(
    'UPDATE ucp_punishments
        SET reason = ?, edited_at = ?, edited_by = ?, edited_by_name = ?
      WHERE id = ?'
)->execute([$reason, $now, (int)$acc['id'], (string)$acc['username'], $id]);

punish_log_add($pdo, $p, $acc, 'edited',
    'Reason changed', json_encode(['was' => $was, 'now' => $reason], JSON_UNESCAPED_UNICODE));

ok([
    'id'        => $id,
    'reason'    => $reason,
    'edited_at' => $now,
    'edited_by' => (string)$acc['username'],
    'message'   => 'Reason updated. The previous wording is kept in the punishment log.',
]);
