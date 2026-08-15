<?php
/**
 * GET /api/app-templates.php
 *
 * The saved responses. Also reachable inside application.php, which sends
 * them with the review screen so the picker is not a second request.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('app_templates', 60);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_manage($acc)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

ok([
    'rows' => array_map('app_template_out', $pdo->query(
        'SELECT * FROM ucp_app_templates ORDER BY sort_order, id'
    )->fetchAll()),
    'uses' => APP_TEMPLATE_USES,
]);
