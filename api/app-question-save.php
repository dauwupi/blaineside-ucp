<?php
/**
 * POST /api/app-question-save.php
 * Body: one of
 *   { action:"save",    id?, title, prompt, min_words, pinned }
 *   { action:"retire",  id, retired:bool }
 *   { action:"reorder", order:[id,id,...] }
 *   { action:"draw",    draw:int }
 *
 * Everything the Question Manager writes, in one endpoint, because every
 * one of those actions is the same permission check and the same table.
 *
 * Questions are RETIRED, never deleted. An application stores its own copy
 * of the question it asked (see _applications.php), so deleting a row
 * would not corrupt old applications — but it would lose the one thing a
 * retired question is still good for, which is putting it back.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('app_question_save', 40);

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

if ($action === 'draw') {
    $n = max(0, min(20, (int)($in['draw'] ?? 0)));
    $pdo->prepare(
        'INSERT INTO ucp_app_config (name, value, updated_at) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)'
    )->execute(['draw_count', (string)$n, $now]);
    ok(['draw' => $n]);
}

if ($action === 'reorder') {
    $order = is_array($in['order'] ?? null) ? $in['order'] : [];
    $st = $pdo->prepare('UPDATE ucp_app_questions SET sort_order = ?, updated_at = ? WHERE id = ?');
    $i = 0;
    foreach ($order as $qid) {
        $qid = (int)$qid;
        if ($qid > 0) $st->execute([++$i, $now, $qid]);
    }
    ok(['ordered' => $i]);
}

if ($action === 'retire') {
    $id = (int)($in['id'] ?? 0);
    $to = !empty($in['retired']) ? 1 : 0;
    $pdo->prepare('UPDATE ucp_app_questions SET retired = ?, updated_at = ? WHERE id = ?')
        ->execute([$to, $now, $id]);
    ok(['id' => $id, 'retired' => (bool)$to]);
}

/* ---- save (create or update) ---- */
$id     = (int)($in['id'] ?? 0);
$title  = trim((string)($in['title'] ?? ''));
$prompt = trim((string)($in['prompt'] ?? ''));
$min    = max(0, min(2000, (int)($in['min_words'] ?? 0)));
$pinned = !empty($in['pinned']) ? 1 : 0;

if (mb_strlen($title) < 3)   fail('Give the question a title.', 422);
if (mb_strlen($title) > 140) fail('That title is too long.', 422);
if (mb_strlen($prompt) < 10) fail('Write the prompt the applicant will read.', 422);

if ($id > 0) {
    $pdo->prepare(
        'UPDATE ucp_app_questions
            SET title = ?, prompt = ?, min_words = ?, pinned = ?, updated_at = ?
          WHERE id = ?'
    )->execute([$title, $prompt, $min, $pinned, $now, $id]);
} else {
    $next = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM ucp_app_questions')
                     ->fetchColumn();
    $pdo->prepare(
        'INSERT INTO ucp_app_questions
           (title, prompt, min_words, pinned, retired, sort_order,
            created_by, created_by_name, created_at, updated_at)
         VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?)'
    )->execute([$title, $prompt, $min, $pinned, $next,
                (int)$acc['id'], $acc['username'], $now, $now]);
    $id = (int)$pdo->lastInsertId();
}

$st = $pdo->prepare('SELECT * FROM ucp_app_questions WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();

ok(['question' => $row ? app_question_out($row) : null]);
