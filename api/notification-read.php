<?php
/**
 * POST /api/notification-read.php
 * Body: { id } | { all: true }
 *
 * Marks one notification read, or all of them.
 *
 * Scoped to the caller's own rows in the WHERE clause rather than checked
 * first and then updated: there is no id anybody can send that reaches
 * somebody else's notification.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('notification_read', 60);

$pdo = db();
$acc = current_account($pdo);

if (!notes_available($pdo)) ok(['unread' => 0]);

$in  = read_input();
$now = time();

if (!empty($in['all'])) {
    $pdo->prepare('UPDATE ucp_notifications SET read_at = ?
                    WHERE account_id = ? AND read_at IS NULL')
        ->execute([$now, (int)$acc['id']]);
} else {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Which notification?', 422);
    $pdo->prepare('UPDATE ucp_notifications SET read_at = ?
                    WHERE id = ? AND account_id = ? AND read_at IS NULL')
        ->execute([$now, $id, (int)$acc['id']]);
}

ok(['unread' => notes_unread($pdo, (int)$acc['id'])]);
