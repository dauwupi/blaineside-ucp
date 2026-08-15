<?php
/**
 * POST /api/app-template-save.php
 * Body: one of
 *   { action:"save",    id?, title, body, use_for }
 *   { action:"delete",  id }
 *   { action:"reorder", order:[id,...] }
 *
 * Templates ARE deleted rather than retired, unlike questions: a template
 * is never quoted by an old application — what it produced was copied into
 * the feedback field and edited before it was sent, so removing one
 * changes nothing that has already happened.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('app_template_save', 40);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_manage($acc)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$in     = read_input();
$action = (string)($in['action'] ?? 'save');
$now    = time();

if ($action === 'reorder') {
    $order = is_array($in['order'] ?? null) ? $in['order'] : [];
    $st = $pdo->prepare('UPDATE ucp_app_templates SET sort_order = ?, updated_at = ? WHERE id = ?');
    $i = 0;
    foreach ($order as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) $st->execute([++$i, $now, $tid]);
    }
    ok(['ordered' => $i]);
}

if ($action === 'delete') {
    $id = (int)($in['id'] ?? 0);
    $pdo->prepare('DELETE FROM ucp_app_templates WHERE id = ?')->execute([$id]);
    ok(['deleted' => $id]);
}

$id    = (int)($in['id'] ?? 0);
$title = trim((string)($in['title'] ?? ''));
$body  = trim((string)($in['body'] ?? ''));
$use   = strtolower(trim((string)($in['use_for'] ?? 'either')));
if (!isset(APP_TEMPLATE_USES[$use])) $use = 'either';

if (mb_strlen($title) < 3)   fail('Give the template a title.', 422);
if (mb_strlen($title) > 140) fail('That title is too long.', 422);
if (mb_strlen($body) < 10)   fail('Write the response itself.', 422);

if ($id > 0) {
    $pdo->prepare(
        'UPDATE ucp_app_templates SET title = ?, body = ?, use_for = ?, updated_at = ? WHERE id = ?'
    )->execute([$title, $body, $use, $now, $id]);
} else {
    $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ucp_app_templates')
                     ->fetchColumn();
    $pdo->prepare(
        'INSERT INTO ucp_app_templates
           (title, body, use_for, sort_order, created_by, created_by_name, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([$title, $body, $use, $next, (int)$acc['id'], $acc['username'], $now, $now]);
    $id = (int)$pdo->lastInsertId();
}

$st = $pdo->prepare('SELECT * FROM ucp_app_templates WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();

ok(['template' => $row ? app_template_out($row) : null]);
