<?php
/**
 * POST /api/report-comment.php
 * Body: { id, body, staff_only?: bool }
 *
 * Adds a comment to a staff report.
 *
 * Both sides write here. The reporter can add what they forgot — the form
 * says a report cannot be edited, and this is the honest answer to that:
 * supplementary information goes in a comment, where it is timestamped
 * after the fact rather than silently folded into the original.
 *
 * `staff_only` is staff-side only, and is checked here rather than trusted:
 * a reporter posting with that flag set would otherwise write a comment
 * they could not then see.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('report_comment', 20);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$in   = read_input();
$id   = (int)($in['id'] ?? 0);
$body = trim((string)($in['body'] ?? ''));

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) fail('There is no staff report with that number.', 404);

$block = report_view_block($pdo, $acc, $r);
if ($block !== null) json_out(['ok' => false, 'error' => $block], 403);

$staff = report_may_panel($pdo, $acc);
$mine  = report_is_mine($acc, $r);

if ($body === '')                     fail('Write something first.', 422);
if (mb_strlen($body) > BS_REPORT_COMMENT_MAX) {
    fail('That is too long. Keep it under ' . number_format(BS_REPORT_COMMENT_MAX)
       . ' characters.', 422);
}

/* Closed to the reporter, open to staff. That asymmetry is deliberate:
   closing comments is how a handler stops a thread going in circles, and it
   should not also stop them recording what they found. */
if (!$staff) {
    if (!$mine) fail('That report is not yours.', 403);
    if ((string)$r['status'] !== 'pending') {
        fail('This report has been concluded, so the thread is closed. If there is something '
           . 'new, send a new report rather than adding to a decided one.', 409);
    }
    if (empty($r['comments_enabled'])) {
        fail('Comments on this report have been closed by Staff Management.', 409);
    }
}

$staffOnly = $staff && !empty($in['staff_only']);
$now = time();

$pdo->prepare(
    'INSERT INTO ucp_report_comments
        (report_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'], $staff ? 1 : 0,
            $staffOnly ? 1 : 0, $body, $now]);

$pdo->prepare('UPDATE ucp_reports SET updated_at = ? WHERE id = ?')->execute([$now, $id]);

if ($staff) {
    report_log_add($pdo, $id, $acc, 'comment',
        $staffOnly ? 'Left a staff-only note.' : 'Replied to the reporter.');
} else {
    report_log_add($pdo, $id, $acc, 'comment', 'The reporter added a comment.');
}

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);

ok(['message' => 'Posted.', 'report' => report_out($pdo, $st->fetch(), $acc)]);
