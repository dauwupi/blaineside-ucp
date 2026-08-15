<?php
/**
 * GET /api/application.php?id=123
 *
 * One application, in full.
 *
 * Two audiences, one endpoint, different payloads. Support Staff get the
 * applicant block, the addresses, the shared-IP matches, the history and
 * the log. The APPLICANT gets their own answers and their own feedback and
 * nothing else — never the addresses, never who claimed it, never the
 * matches. Everything sensitive is added inside `if ($staff)` below, so
 * there is one place to check rather than a dozen.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('application_view', 90);

$pdo = db();
$acc = current_account($pdo);

if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT a.*, u.username FROM ucp_applications a
       JOIN ucp_accounts u ON u.id = a.account_id
      WHERE a.id = ? LIMIT 1'
);
$st->execute([$id]);
$app = $st->fetch();
if (!$app) fail('That application no longer exists.', 404);

$me    = (int)$acc['id'];
$owner = (int)$app['account_id'] === $me;
$staff = app_may_panel($acc, $pdo);

if (!$owner && !$staff) fail('That isn\'t your application.', 403);
/* A draft belongs to nobody but its author until it is sent. */
if ($app['status'] === 'draft' && !$owner) fail('That application hasn\'t been sent yet.', 404);

$out = app_row_out($app);
$out['player']  = ['id' => (int)$app['account_id'], 'name' => $app['username']];
$out['answers'] = app_answers($pdo, $id);
$out['owner']   = $owner;
$out['staff']   = $staff;

if ($staff) {
    /* The reading aid, and only here — inside the staff branch, next to
       the addresses and the history. The applicant's own view of this
       same application never carries it. */
    $out['answers']   = assist_attach($pdo, $out['answers']);
    $out['applicant'] = app_applicant($pdo, (int)$app['account_id']);
    $out['ips']       = app_ips_for($pdo, (int)$app['account_id']);
    $out['matches']   = app_ip_matches($pdo, (int)$app['account_id']);
    $out['history']   = app_history($pdo, (int)$app['account_id'], $id);
    $out['log']       = app_log_list($pdo, $id);
    $out['may']       = app_may_act($pdo, $acc, $app);
    $out['mine']      = $app['claimed_by'] !== null && (int)$app['claimed_by'] === $me;
    /* Templates travel with the application so the review screen is one
       request, not two — the picker is useless without them anyway. */
    $out['templates'] = array_map('app_template_out', $pdo->query(
        'SELECT * FROM ucp_app_templates ORDER BY sort_order, id'
    )->fetchAll());
} else {
    $out['history'] = app_history($pdo, $me, $id);
}

ok($out);
