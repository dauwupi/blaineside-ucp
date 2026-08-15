<?php
/**
 * POST /api/notification-delete.php
 * Body: { id } | { all: true }
 *
 * Throws a notification away.
 *
 * Deliberately separate from marking one read. The two are different
 * intentions — "I have dealt with this and want the record" and "this is
 * not worth the space" — and a single control that did both would be wrong
 * for whichever one the reader meant.
 *
 * Deleting a notification never touches what it pointed at. The appeal, the
 * report and the thread are all still there; only the pointer goes.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('notification_delete', 60);

$pdo = db();
$acc = current_account($pdo);

if (!notes_available($pdo)) ok(['unread' => 0, 'deleted' => 0]);

$in = read_input();

if (!empty($in['all'])) {
    $n = notes_delete($pdo, (int)$acc['id']);
} else {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) fail('Which notification?', 422);
    $n = notes_delete($pdo, (int)$acc['id'], $id);
}

ok(['deleted' => $n, 'unread' => notes_unread($pdo, (int)$acc['id'])]);
