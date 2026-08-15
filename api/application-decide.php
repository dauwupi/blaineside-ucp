<?php
/**
 * POST /api/application-decide.php
 * Body: { id, outcome: "pass" | "deny", feedback, template_id? }
 *
 * Passes or denies an application.
 *
 * Feedback is REQUIRED on a denial and optional on a pass, which is the
 * one asymmetry in this file worth defending: a player who is refused and
 * told nothing applies again with the same answers, and the second review
 * costs the same as the first. A player who is accepted needs no
 * explanation of what they got right.
 *
 * There is no comment thread anywhere in this feature. The feedback field
 * is the whole conversation, by design — the alternative is a queue that
 * Support Staff have to keep coming back to.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('application_decide', 30);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_panel($acc)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$in       = read_input();
$id       = (int)($in['id'] ?? 0);
$outcome  = strtolower(trim((string)($in['outcome'] ?? '')));
$feedback = trim((string)($in['feedback'] ?? ''));
$template = (int)($in['template_id'] ?? 0);

if ($outcome !== 'pass' && $outcome !== 'deny') {
    fail('Choose whether this application passes or is denied.', 422);
}

$st = $pdo->prepare(
    'SELECT a.*, u.username FROM ucp_applications a
       JOIN ucp_accounts u ON u.id = a.account_id WHERE a.id = ? LIMIT 1'
);
$st->execute([$id]);
$app = $st->fetch();

if (!$app)                        fail('That application no longer exists.', 404);
if ($app['status'] !== 'pending') fail('That application has already been decided.', 409);

if (!app_may_act($pdo, $acc, $app)) {
    fail($app['claimed_by_name'] . ' has claimed this one. Ask them, or take it over first.', 403);
}

if ($outcome === 'deny' && mb_strlen($feedback) < 20) {
    fail('Tell them what to change. A denial with no feedback means they apply '
         . 'again with the same answers.', 422);
}

$status = $outcome === 'pass' ? 'passed' : 'denied';
$now    = time();
$me     = (int)$acc['id'];

$pdo->prepare(
    'UPDATE ucp_applications
        SET status = ?, feedback = ?, decided_by = ?, decided_by_name = ?,
            decided_at = ?, updated_at = ?,
            claimed_by = ?, claimed_by_name = ?, claimed_at = ?
      WHERE id = ? AND status = ?'
)->execute([
    $status, $feedback !== '' ? $feedback : null, $me, $acc['username'],
    $now, $now, $me, $acc['username'], $app['claimed_at'] ?? $now,
    $id, 'pending',
]);

if ($template > 0) {
    try {
        $pdo->prepare('UPDATE ucp_app_templates SET used_count = used_count + 1 WHERE id = ?')
            ->execute([$template]);
    } catch (Throwable $e) {
    }
}

app_log($pdo, $id, $acc, $status, 'Attempt ' . (int)$app['attempt']);

/* The applicant always hears, either way — that is the whole point of the
   dashboard notice disappearing rather than turning into a third state. */
notify($pdo, (int)$app['account_id'], 'application', $status,
    $status === 'passed'
        ? 'Your application passed'
        : 'Your application was denied',
    ['body' => $status === 'passed'
        ? 'Blaine County is open to you. Welcome in.'
        : 'There is feedback on your Application page explaining what to change.',
     'url'  => '/dashboard/application',
     'actor_id' => $me, 'actor_name' => $acc['username'],
     'self' => true]);

ok(['id' => $id, 'status' => $status]);
