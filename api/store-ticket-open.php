<?php
/**
 * POST /api/store-ticket-open.php
 * Body: { subject, body, order_ref? }
 *
 * Opens a ticket. The first message IS the ticket — there is no separate
 * description field, because two boxes asking for the same thing produce
 * one that is filled in and one that says "see above".
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('store_ticket_open', 6);

$pdo = db();
$acc = current_account($pdo);

if (!store_available($pdo)) {
    json_out(['ok' => false, 'error' => store_missing_reason()], 409);
}

$in      = read_input();
$subject = trim((string)($in['subject'] ?? ''));
$body    = trim((string)($in['body'] ?? ''));
$order   = trim((string)($in['order_ref'] ?? ''));

if (mb_strlen($subject) < BS_STORE_SUBJECT_MIN) {
    fail('Give the ticket a subject — one line saying what went wrong.', 422);
}
if (mb_strlen($subject) > 140) fail('That subject is too long.', 422);
if (mb_strlen($body) < BS_STORE_BODY_MIN) {
    fail('Say what happened, what you expected, and what you got instead.', 422);
}

$st = $pdo->prepare(
    "SELECT COUNT(*) FROM ucp_store_tickets WHERE account_id = ? AND status <> 'closed'"
);
$st->execute([(int)$acc['id']]);
if ((int)$st->fetchColumn() >= BS_STORE_OPEN_MAX) {
    fail('You already have ' . BS_STORE_OPEN_MAX . ' tickets open. Add to one of those instead.', 429);
}

$now = time();
$pdo->prepare(
    'INSERT INTO ucp_store_tickets
       (account_id, subject, order_ref, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?)'
)->execute([
    (int)$acc['id'], $subject, $order !== '' ? mb_substr($order, 0, 40) : null,
    'open', $now, $now,
]);
$id = (int)$pdo->lastInsertId();

store_add_message($pdo, $id, $acc, $body, false);

/* The reply counter counts REPLIES. The message that opened the ticket is
   the ticket, so it is not one. */
$pdo->prepare('UPDATE ucp_store_tickets SET replies = 0 WHERE id = ?')->execute([$id]);

try {
    $st = $pdo->prepare('SELECT id FROM ucp_accounts WHERE admin_rank >= ? AND status = ?');
    $st->execute([BS_STORE_STAFF_RANK, 'active']);
    notify_all($pdo, array_column($st->fetchAll(), 'id'), 'system', 'store_ticket',
        'New purchase support ticket',
        ['body' => $acc['username'] . ': ' . mb_substr($subject, 0, 120),
         'url'  => '/dashboard/store#ticket=' . $id,
         'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username'],
         'dedupe' => 'store-ticket-' . $id]);
} catch (Throwable $e) {
}

ok(['id' => $id]);
