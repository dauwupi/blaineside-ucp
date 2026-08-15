<?php
/**
 * POST /api/report-verdict.php
 * Body: { id, category, outcome, comment, staff_only?: bool }
 *
 * Concludes a staff report.
 *
 * Three things this endpoint insists on:
 *
 *   1. A CATEGORY. It is what the information panel promises within 24-48
 *      hours, and concluding without one leaves the promise unkept on the
 *      one report where it mattered.
 *
 *   2. AN OUTCOME. "Concluded" on its own tells the reporter that somebody
 *      read it and nothing else — which is the complaint the whole queue
 *      exists to answer.
 *
 *   3. A CLOSING COMMENT, always. The reporter is shown it. A conclusion
 *      with no reason is the single most common cause of the same person
 *      filing the same report again, and of them filing it in Discord
 *      instead the second time.
 *
 * What it deliberately does NOT do is punish anybody. There is no field
 * here that demotes, warns or removes a member of staff: that is a
 * conversation and a decision that happens off this page, and a button
 * which did it in one click would be the most dangerous control in the UCP.
 * The report records what was decided; a human does the deciding.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('report_verdict', 20);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$in        = read_input();
$id        = (int)($in['id'] ?? 0);
$category  = trim((string)($in['category'] ?? ''));
$outcome   = trim((string)($in['outcome'] ?? ''));
$comment   = trim((string)($in['comment'] ?? ''));
$staffOnly = !empty($in['staff_only']);

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) fail('There is no staff report with that number.', 404);

if (!report_may_panel($pdo, $acc)) {
    json_out(['ok' => false, 'error' => report_panel_reason()], 403);
}
/* A Staff Management holder cannot act on a report naming them; Management
   and Founders can, and the page warns them rather than stopping them. */
if (report_subject_blind($pdo, $acc) && report_is_subject($pdo, $id, (int)$acc['id'])) {
    json_out(['ok' => false, 'error' => 'This report names you, and the Staff Report Panel is '
            . 'held through a sub-group. Management will decide it.'], 403);
}
if ((string)$r['status'] !== 'pending') {
    fail('This report has already been concluded.', 409);
}

if (!isset(report_categories()[$category])) {
    fail('Choose which kind of report this is.', 422);
}
if (!isset(report_outcomes()[$outcome])) {
    fail('Choose what was done about it.', 422);
}
if ($comment === '') {
    fail('Write the reason for this decision. The reporter is shown it, so write it to them.',
         422);
}
if (mb_strlen($comment) > BS_REPORT_COMMENT_MAX) {
    fail('That is too long. Keep the closing comment under '
       . number_format(BS_REPORT_COMMENT_MAX) . ' characters.', 422);
}

/* A rejected report is its own status rather than a concluded one with a
 * rejected outcome. The two read differently to the person who sent it —
 * "we looked at this and took no action" is not "this was never reviewed" —
 * and the queue counts them apart for the same reason.
 *
 * Rejecting is a category decision, so it is taken from the category and
 * the outcome is forced to match rather than allowing the two to disagree. */
$rejected = $category === 'rejected';
if ($rejected) $outcome = 'rejected';
$status = $rejected ? 'rejected' : 'concluded';

$now = time();

/* The closing comment is written FIRST, in the same second as the stamp on
 * the report row: report_comments() identifies the verdict comment by that
 * match rather than by a column. Staff-only would hide the decision from
 * the one person it is addressed to, so it is not offered here. */
$pdo->prepare(
    'INSERT INTO ucp_report_comments
        (report_id, author_id, author_name, author_is_staff, staff_only, body, created_at)
     VALUES (?, ?, ?, 1, 0, ?, ?)'
)->execute([$id, (int)$acc['id'], (string)$acc['username'], $comment, $now]);

/* Comments close with the decision. The report is answered; anything that
 * follows is a new report or a conversation with Staff Management, and a
 * thread that stays open under a conclusion invites neither. */
$pdo->prepare(
    'UPDATE ucp_reports
        SET status = ?, category = ?, outcome = ?, comments_enabled = 0,
            concluded_at = ?, concluded_by = ?, concluded_by_name = ?, updated_at = ?
      WHERE id = ?'
)->execute([$status, $category, $outcome, $now, (int)$acc['id'],
            (string)$acc['username'], $now, $id]);

/* What was decided, never who decided it — the notification obeys the same
   rule as the page it links to. */
notify($pdo, (int)$r['account_id'], 'report', 'verdict',
    'Your staff report was ' . ($rejected ? 'rejected' : 'concluded'),
    ['body' => report_category_label($category) . ' — ' . report_outcome_label($outcome),
     'url'  => '/dashboard/reports?id=' . $id,
     'actor_id' => (int)$acc['id']]);

report_log_add($pdo, $id, $acc, 'concluded',
    report_category_label($category) . ' — ' . report_outcome_label($outcome) . '.');

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);

ok([
    'message' => $rejected ? 'Report rejected. The reporter has been told why.'
                           : 'Report concluded. The reporter has been told what was decided.',
    'report'  => report_out($pdo, $st->fetch(), $acc),
]);
