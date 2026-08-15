<?php
/**
 * GET /api/report.php?id=123
 *
 * One staff report, shaped for whoever is asking.
 *
 * The reporter gets their own report without the handler's name, the
 * staff-only comments or the running log. A panel member gets all of it.
 * Somebody the report NAMES gets a refusal, whatever their rank — see rule
 * 2 in _reports.php.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

throttle('report_open', 90);

$pdo = db();
$acc = current_account($pdo);

if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) fail('Which report?', 422);

$st = $pdo->prepare('SELECT * FROM ucp_reports WHERE id = ? LIMIT 1');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) fail('There is no staff report with that number.', 404);

$block = report_view_block($pdo, $acc, $r);
if ($block !== null) json_out(['ok' => false, 'error' => $block], 403);

/* Logged before the response is built, so a handler who opens a report and
   closes it without doing anything still leaves a trace. Repeat visits by
   the same person inside an hour collapse into one line. */
if (report_may_panel($pdo, $acc) && !report_is_mine($acc, $r)) {
    /* Reading a report about yourself is the one view worth spelling out.
       Only Management and Founders can reach it, it is legitimate, and it
       is exactly the line somebody will want to find later. */
    report_log_add($pdo, $id, $acc,
        report_is_subject($pdo, $id, (int)$acc['id']) ? 'viewed_named' : 'viewed',
        report_is_subject($pdo, $id, (int)$acc['id'])
            ? 'Opened a report that names them.' : null);
}

ok(['authenticated' => true, 'report' => report_out($pdo, $r, $acc)]);
