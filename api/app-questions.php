<?php
/**
 * GET /api/app-questions.php?retired=0
 *
 * The Question Manager's list, plus the draw setting under it.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('app_questions', 60);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_manage($acc)) {
    json_out(['ok' => false, 'error' => app_manage_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$retired = !empty($_GET['retired']);

$sql = 'SELECT * FROM ucp_app_questions';
if (!$retired) $sql .= ' WHERE retired = 0';
$sql .= ' ORDER BY retired, pinned DESC, sort_order, id';

$rows = array_map('app_question_out', $pdo->query($sql)->fetchAll());

$pool = 0;
foreach ($rows as $r) if (!$r['pinned'] && !$r['retired']) $pool++;

ok([
    'rows'  => $rows,
    'draw'  => app_draw_count($pdo),
    'pool'  => $pool,
    'shown_retired' => $retired,
]);
