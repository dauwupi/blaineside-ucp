<?php
/**
 * GET /api/store-ticket.php?id=41
 *
 * One ticket and its whole thread. The author or Management; everybody
 * else gets 404 rather than 403, because "that ticket exists but is not
 * yours" is itself a fact about somebody else's account.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';

throttle('store_ticket', 90);

$pdo = db();
$acc = current_account($pdo);

if (!store_available($pdo)) {
    json_out(['ok' => false, 'error' => store_missing_reason()], 409);
}

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare(
    'SELECT t.*, u.username FROM ucp_store_tickets t
       JOIN ucp_accounts u ON u.id = t.account_id WHERE t.id = ? LIMIT 1'
);
$st->execute([$id]);
$t = $st->fetch();

if (!$t || !store_may_read($acc, $t)) fail('That ticket no longer exists.', 404);

$out = store_ticket_out($t, $acc);
$out['messages'] = store_messages($pdo, $id);
$out['staff']    = store_is_staff($acc);
$out['may_reply'] = $t['status'] !== 'closed';

ok($out);
