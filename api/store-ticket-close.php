<?php
/**
 * POST /api/store-ticket-close.php
 * Body: { id, reopen?: bool }
 *
 * Either side can close a ticket: the player because they are satisfied,
 * Management because it is dealt with. Only Management can reopen one —
 * otherwise a closed ticket is not closed, it is a button.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('store_ticket_close', 30);

$pdo = db();
$acc = current_account($pdo);

if (!store_available($pdo)) {
    json_out(['ok' => false, 'error' => store_missing_reason()], 409);
}

$in     = read_input();
$id     = (int)($in['id'] ?? 0);
$reopen = !empty($in['reopen']);

$st = $pdo->prepare('SELECT * FROM ucp_store_tickets WHERE id = ? LIMIT 1');
$st->execute([$id]);
$t = $st->fetch();

if (!$t || !store_may_read($acc, $t)) fail('That ticket no longer exists.', 404);

$now = time();

if ($reopen) {
    if (!store_is_staff($acc)) fail('Only Management can reopen a ticket.', 403);
    $pdo->prepare(
        'UPDATE ucp_store_tickets
            SET status = ?, closed_at = NULL, closed_by = NULL, closed_by_name = NULL, updated_at = ?
          WHERE id = ?'
    )->execute(['open', $now, $id]);
    ok(['id' => $id, 'status' => 'open']);
}

if ($t['status'] === 'closed') fail('That ticket is already closed.', 409);

$pdo->prepare(
    'UPDATE ucp_store_tickets
        SET status = ?, closed_at = ?, closed_by = ?, closed_by_name = ?, updated_at = ?
      WHERE id = ?'
)->execute(['closed', $now, (int)$acc['id'], $acc['username'], $now, $id]);

/* If Management closed somebody else's ticket, that person should hear it
   from the bell rather than by coming back to look. */
if (store_is_staff($acc) && (int)$t['account_id'] !== (int)$acc['id']) {
    notify($pdo, (int)$t['account_id'], 'system', 'store_closed',
        'Your support ticket was closed',
        ['body' => mb_substr($t['subject'], 0, 120),
         'url'  => '/dashboard/store#ticket=' . $id,
         'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username']]);
}

ok(['id' => $id, 'status' => 'closed']);
