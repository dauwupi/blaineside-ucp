<?php
/**
 * POST /api/store-ticket-reply.php
 * Body: { id, body }
 *
 * Adds to a ticket. The status follows from who replied — see
 * store_add_message() — so nobody has to keep a dropdown honest.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('store_ticket_reply', 30);

$pdo = db();
$acc = current_account($pdo);

if (!store_available($pdo)) {
    json_out(['ok' => false, 'error' => store_missing_reason()], 409);
}

$in   = read_input();
$id   = (int)($in['id'] ?? 0);
$body = trim((string)($in['body'] ?? ''));

$st = $pdo->prepare('SELECT * FROM ucp_store_tickets WHERE id = ? LIMIT 1');
$st->execute([$id]);
$t = $st->fetch();

if (!$t || !store_may_read($acc, $t)) fail('That ticket no longer exists.', 404);
if ($t['status'] === 'closed')        fail('That ticket is closed. Open a new one.', 409);
if (mb_strlen($body) < 2)             fail('Write something first.', 422);

$staff = store_is_staff($acc);
store_add_message($pdo, $id, $acc, $body, $staff);

/* Tell the other side, not the side that just typed. */
if ($staff) {
    notify($pdo, (int)$t['account_id'], 'system', 'store_reply',
        'Management replied to your ticket',
        ['body' => mb_substr($t['subject'], 0, 120),
         'url'  => '/dashboard/store#ticket=' . $id,
         'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username']]);
} else {
    try {
        $st = $pdo->prepare('SELECT id FROM ucp_accounts WHERE admin_rank >= ? AND status = ?');
        $st->execute([BS_STORE_STAFF_RANK, 'active']);
        notify_all($pdo, array_column($st->fetchAll(), 'id'), 'system', 'store_reply',
            'Reply on a support ticket',
            ['body' => $acc['username'] . ': ' . mb_substr($t['subject'], 0, 120),
             'url'  => '/dashboard/store#ticket=' . $id,
             'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username'],
             'dedupe' => 'store-reply-' . $id]);
    } catch (Throwable $e) {
    }
}

ok(['id' => $id]);
