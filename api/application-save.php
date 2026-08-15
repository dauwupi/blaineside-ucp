<?php
/**
 * POST /api/application-save.php
 * Body: { id, answers: { <answer_id>: "text", ... } }
 *
 * The autosave. Called a couple of seconds after typing stops and again on
 * leaving the page, so it has to be cheap and it has to be safe to call
 * with nothing changed — both of which it is.
 *
 * It writes ONLY to answers on a draft owned by the caller. There is no
 * path here to a submitted application: an answer that could still be
 * edited after submission would make the thing Support Staff read
 * different from the thing that was sent.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('application_save', 120);

$pdo = db();
$acc = current_account($pdo);

if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$in      = read_input();
$id      = (int)($in['id'] ?? 0);
$answers = is_array($in['answers'] ?? null) ? $in['answers'] : [];

$st = $pdo->prepare('SELECT * FROM ucp_applications WHERE id = ? LIMIT 1');
$st->execute([$id]);
$app = $st->fetch();

if (!$app)                                       fail('That application no longer exists.', 404);
if ((int)$app['account_id'] !== (int)$acc['id']) fail('That isn\'t your application.', 403);
if ($app['status'] !== 'draft')                  fail('This application has already been sent.', 409);

$upd = $pdo->prepare(
    'UPDATE ucp_app_answers SET body = ? WHERE id = ? AND application_id = ?'
);
$n = 0;
foreach ($answers as $answerId => $text) {
    $answerId = (int)$answerId;
    if ($answerId <= 0) continue;
    $upd->execute([mb_substr((string)$text, 0, 20000), $answerId, $id]);
    $n++;
}

$pdo->prepare('UPDATE ucp_applications SET updated_at = ? WHERE id = ?')
    ->execute([time(), $id]);

ok(['saved' => $n, 'at' => time()]);
