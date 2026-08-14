<?php
/**
 * GET /api/appeal.php?id=123
 * GET /api/appeal.php            (mine — the one I have open, if any)
 *
 * One appeal, as whoever is asking is allowed to see it.
 *
 * The difference between the two views is made HERE, not on the page.
 * Staff-only comments and the running log are not fetched at all for the
 * appellant — see _appeals.php. There is no version of the response that
 * contains them and a flag saying not to show them.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

throttle('appeal_read', 90);

$pdo = db();
$acc = current_account_or_locked($pdo);

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}

$id = (int)($_GET['id'] ?? 0);

if ($id < 1) {
    // No id: give them their own open appeal, which is what "Appeal your
    // Punishment" links to once one exists.
    $st = $pdo->prepare(
        'SELECT * FROM ucp_appeals WHERE account_id = ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([(int)$acc['id']]);
    $a = $st->fetch();
    if (!$a) json_out(['ok' => true, 'authenticated' => true, 'appeal' => null], 200);
} else {
    $st = $pdo->prepare('SELECT * FROM ucp_appeals WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $a = $st->fetch();
    if (!$a) json_out(['ok' => false, 'error' => 'There is no appeal with that number.'], 404);
}

/* Refused AFTER the row is loaded and BEFORE anything about it is returned,
   so the answer is identical whether or not the appeal exists — the same
   rule api/admin-account.php follows. */
if (!appeal_may_view($acc, $a)) {
    json_out(['ok' => false, 'code' => 'not_yours',
              'error' => 'That appeal isn\'t yours.'], 403);
}

// Staff views are recorded. The appellant reading their own is not — it is
// their appeal, and a log of them refreshing it tells nobody anything.
if (appeal_is_staff($acc) && (int)$acc['id'] !== (int)$a['account_id']) {
    appeal_log_add($pdo, (int)$a['id'], $acc, 'viewed');
}

ok(['authenticated' => true, 'appeal' => appeal_out($pdo, $a, $acc)]);
