<?php
/**
 * POST /api/appeal-comment.php
 * Body: { id, body, staff_only?: true }
 *
 * Adds a comment to an appeal. Both sides use this: the appellant replying,
 * and staff talking either to them or to each other.
 *
 * staff_only is refused for anyone who isn't staff — not ignored. A player
 * sending it is either confused or probing, and both deserve an answer.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('appeal_comment', 20);

$pdo = db();
$acc = current_account_or_locked($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}

$in        = read_input();
$id        = (int)($in['id'] ?? 0);
$body      = trim((string)($in['body'] ?? ''));
$staffOnly = !empty($in['staff_only']);

$st = $pdo->prepare('SELECT * FROM ucp_appeals WHERE id = ? LIMIT 1');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) fail('There is no appeal with that number.', 404);

if (!appeal_may_view($acc, $a)) {
    json_out(['ok' => false, 'error' => 'That appeal isn\'t yours.'], 403);
}

/* Staff powers apply to other people's appeals, not your own. A staff
   member appealing their own ban is the appellant here — they don't get to
   leave a staff-only comment on their own case, or bypass a closed thread. */
$staff = appeal_is_staff($acc) && (int)$acc['id'] !== (int)$a['account_id'];

if ($staffOnly && !$staff) {
    json_out(['ok' => false, 'error' => 'Only staff can leave a staff-only comment.'], 403);
}

/* Comments closed.
 *
 * Closing a thread stops the APPELLANT, not the staff working it. A handler
 * closes comments because an appeal has turned into an argument, and they
 * still need to record what they decide and why. */
if (empty($a['comments_enabled']) && !$staff) {
    json_out(['ok' => false,
              'error' => 'Comments are closed on this appeal.'], 409);
}

/* A concluded appeal takes no more replies from the appellant. Staff can
   still add to it — a note explaining a verdict a week later belongs on the
   appeal, not in Discord. */
if ($a['status'] !== 'pending' && !$staff) {
    json_out(['ok' => false,
              'error' => 'This appeal has been decided, so it is closed to replies.'], 409);
}

if ($body === '') fail('Write something first.', 422);
if (mb_strlen($body) > BS_APPEAL_COMMENT_MAX) {
    fail('That comment is too long. Keep it under '
       . number_format(BS_APPEAL_COMMENT_MAX) . ' characters.', 422);
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_appeal_comments
        (appeal_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'],
            $staff ? 1 : 0, $staffOnly ? 1 : 0, $body, $now]);

$pdo->prepare('UPDATE ucp_appeals SET updated_at = ? WHERE id = ?')->execute([$now, $id]);

if ($staff) {
    appeal_log_add($pdo, $id, $acc, 'commented',
        $staffOnly ? 'Left a staff-only comment.' : 'Replied to the appellant.');
}

ok(['id' => $id, 'message' => 'Comment added.']);
