<?php
/**
 * POST /api/application-submit.php
 * Body: { id, answers: { <answer_id>: "text" } }
 *
 * Sends the draft. Saves one last time first, so nothing typed in the
 * final two seconds is lost between the autosave and the button.
 *
 * The minimums are enforced HERE as well as on the page. The page
 * counts characters to be helpful; this counts them because the page can be
 * edited by anyone with developer tools open, and a one-word application
 * costs a Support Staff member a review slot either way.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('application_submit', 6);

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

/* Last write, then read back — so what is validated below is exactly what
   is stored, not what the browser claims it sent. */
$upd = $pdo->prepare('UPDATE ucp_app_answers SET body = ? WHERE id = ? AND application_id = ?');
foreach ($answers as $answerId => $text) {
    $answerId = (int)$answerId;
    if ($answerId > 0) $upd->execute([mb_substr((string)$text, 0, 20000), $answerId, $id]);
}

$rows = app_answers($pdo, $id);
if (!$rows) fail('This application has no questions on it. Start a new one.', 409);

foreach ($rows as $i => $a) {
    $n = $i + 1;
    if ($a['chars'] === 0) {
        fail('Question ' . $n . ' (' . $a['title'] . ') hasn\'t been answered.', 422);
    }
    if ($a['min_chars'] > 0 && $a['chars'] < $a['min_chars']) {
        fail('Question ' . $n . ' (' . $a['title'] . ') needs at least '
             . $a['min_chars'] . ' characters. You have written ' . $a['chars'] . '.', 422);
    }
}

$now = time();
$pdo->prepare(
    'UPDATE ucp_applications
        SET status = ?, submitted_at = ?, submit_ip = ?, updated_at = ?
      WHERE id = ? AND status = ?'
)->execute(['pending', $now, substr(client_ip(), 0, 45), $now, $id, 'draft']);

/* Times asked is counted at submission, not when the draft was drawn: an
   abandoned draft did not ask anybody anything. */
try {
    $pdo->prepare(
        'UPDATE ucp_app_questions q
            JOIN ucp_app_answers a ON a.question_id = q.id
             SET q.asked_count = q.asked_count + 1
           WHERE a.application_id = ?'
    )->execute([$id]);
} catch (Throwable $e) {
}

app_log($pdo, $id, $acc, 'submitted', 'Attempt ' . (int)$app['attempt']);

/* Everyone who can act on it hears about it. Support Staff and above, which
   is the same audience as the panel — there is no allocation on arrival, so
   there is nobody more specific to tell. */
try {
    $st = $pdo->prepare('SELECT id FROM ucp_accounts WHERE admin_rank >= ? AND status = ?');
    $st->execute([BS_APP_PANEL_RANK, 'active']);
    notify_all($pdo, array_column($st->fetchAll(), 'id'), 'application', 'submitted',
        'New application from ' . $acc['username'],
        ['body' => 'Attempt ' . (int)$app['attempt'] . ' is waiting to be reviewed.',
         'url'  => '/dashboard/applications#id=' . $id,
         'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username'],
         'dedupe' => 'app-new-' . $id]);
} catch (Throwable $e) {
}

ok(['id' => $id]);
