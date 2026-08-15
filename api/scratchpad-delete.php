<?php
/**
 * POST /api/scratchpad-delete.php
 * Body: { id }
 *
 * Removes one note. The person who wrote it, or Management and above.
 *
 * There is no soft delete and no tombstone. A note is not evidence and does
 * not belong to the record — it is one administrator's working memory, and
 * a deleted note leaving a "note removed" scar on the page would tell the
 * next person there was something to find, which is the opposite of what
 * removing it was for.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/_scratchpad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('pad_delete', 30);

$pdo = db();
$acc = current_account($pdo);

if (!pad_may_view($acc)) {
    json_out(['ok' => false,
              'error' => 'The Scratchpad is for ' . rank_name(BS_ADMIN_MIN_RANK)
                       . ' and above.'], 403);
}
if (!pad_available($pdo)) {
    json_out(['ok' => false,
              'error' => 'The Scratchpad isn\'t set up on this server yet.'], 409);
}

$in = read_input();
$id = (int)($in['id'] ?? 0);

$n = pad_by_id($pdo, $id);
if (!$n) fail('That note is already gone.', 404);

if (!pad_may_delete($acc, $n)) {
    json_out(['ok' => false,
              'error' => 'You can only remove a note you wrote yourself. '
                       . rank_name(BS_PAD_ADMIN_RANK) . ' and the Founder can remove any of '
                       . 'them.'], 403);
}

$pdo->prepare('DELETE FROM ucp_scratchpad WHERE id = ?')->execute([$id]);

ok(['id' => $id, 'message' => 'Note removed.']);
