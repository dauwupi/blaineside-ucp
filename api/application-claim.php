<?php
/**
 * POST /api/application-claim.php
 * Body: { id, action: "claim" | "release" }
 *
 * The lock. Claiming is what stops two Support Staff writing feedback on
 * one application, and it is deliberately the cheapest possible thing:
 * one row, one name, one timestamp, no queue and no assignment ceremony.
 *
 * A claim nobody has touched for BS_APP_CLAIM_IDLE can be taken by anyone.
 * Without that, one person opening an application and closing the tab
 * would park it forever, and the fix would be a database edit.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('application_claim', 30);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_panel($acc, $pdo)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$in     = read_input();
$id     = (int)($in['id'] ?? 0);
$action = (string)($in['action'] ?? 'claim');

$st = $pdo->prepare('SELECT * FROM ucp_applications WHERE id = ? LIMIT 1');
$st->execute([$id]);
$app = $st->fetch();

if (!$app)                        fail('That application no longer exists.', 404);
if ($app['status'] !== 'pending') fail('That application has already been decided.', 409);

$me  = (int)$acc['id'];
$now = time();

if ($action === 'release') {
    if (!app_may_act($pdo, $acc, $app)) {
        fail('That application is claimed by ' . $app['claimed_by_name'] . '.', 403);
    }
    $pdo->prepare(
        'UPDATE ucp_applications
            SET claimed_by = NULL, claimed_by_name = NULL, claimed_at = NULL, updated_at = ?
          WHERE id = ?'
    )->execute([$now, $id]);
    app_log($pdo, $id, $acc, 'released');
    ok(['claimed' => null]);
}

if (!app_may_act($pdo, $acc, $app)) {
    fail($app['claimed_by_name'] . ' is reviewing this one.', 409);
}

$pdo->prepare(
    'UPDATE ucp_applications
        SET claimed_by = ?, claimed_by_name = ?, claimed_at = ?, updated_at = ?
      WHERE id = ?'
)->execute([$me, $acc['username'], $now, $now, $id]);

$took = $app['claimed_by'] !== null && (int)$app['claimed_by'] !== $me;
app_log($pdo, $id, $acc, 'claimed',
    $took ? 'Taken from ' . $app['claimed_by_name'] : null);

/* The person it was taken from is told. Silently losing a piece of work
   you had open is how two people end up writing the same feedback twice. */
if ($took) {
    notify($pdo, (int)$app['claimed_by'], 'application', 'reclaimed',
        'An application you claimed was taken over',
        ['body' => $acc['username'] . ' is now reviewing application #' . $id . '.',
         'url'  => '/dashboard/applications#id=' . $id,
         'actor_id' => $me, 'actor_name' => $acc['username']]);
}

ok(['claimed' => ['id' => $me, 'name' => $acc['username'], 'at' => $now]]);
