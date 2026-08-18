<?php
/**
 * POST /api/store-ticket-open.php
 * Body: { category, order_ref, no_order?, amount?, char_name?, body }
 *
 * Opens a ticket. The first message IS the ticket — there is no separate
 * description field, because two boxes asking for the same thing produce
 * one that is filled in and one that says "see above".
 *
 * There is no subject field either. The title is generated from the
 * account and the order reference, so every ticket in the queue reads the
 * same way and none of them depends on somebody inventing a good subject
 * line while annoyed about money.
 *
 * The order reference is REQUIRED. A player with no order ticks a box and
 * it is recorded as N/A — which is a different thing from an empty field,
 * because it says the question was asked and answered.
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

$in    = read_input();
$cat   = (string)($in['category'] ?? '');
$body  = trim((string)($in['body'] ?? ''));
$order = trim((string)($in['order_ref'] ?? ''));
$noOrd = !empty($in['no_order']);
$amt   = trim((string)($in['amount'] ?? ''));
$char  = trim((string)($in['char_name'] ?? ''));

if (!isset(STORE_CATEGORIES[$cat])) {
    fail('Choose what the ticket is about.', 422);
}
if ($noOrd) {
    $order = STORE_NO_ORDER;
} elseif ($order === '') {
    fail('Give the order reference, or tick that you do not have one.', 422);
}
if (mb_strlen($body) < BS_STORE_BODY_MIN) {
    fail('Say what happened, what you expected, and what you got instead.', 422);
}

$order   = mb_substr($order, 0, 40);
$subject = store_subject($acc['username'], $order);

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
       (account_id, subject, category, order_ref, amount, char_name, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
)->execute([
    (int)$acc['id'], $subject, $cat, $order,
    $amt !== '' ? mb_substr($amt, 0, 40) : null,
    $char !== '' ? mb_substr($char, 0, 60) : null,
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
        ['body' => $acc['username'] . ' — ' . STORE_CATEGORIES[$cat] . ' (' . $order . ')',
         'url'  => '/dashboard/store#ticket=' . $id,
         'actor_id' => (int)$acc['id'], 'actor_name' => $acc['username'],
         'dedupe' => 'store-ticket-' . $id]);
} catch (Throwable $e) {
}

ok(['id' => $id]);
