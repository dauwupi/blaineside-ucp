<?php
/**
 * GET /api/notifications.php?limit=20
 *
 * The bell. Everything addressed to the person asking, newest first, plus
 * the unread count that goes on the badge.
 *
 * Polled by assets/js/ucp.js on every page, so it is deliberately cheap:
 * one indexed SELECT and one COUNT, no joins, and it answers with an empty
 * list rather than an error when the table has not been migrated.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_notify.php';

throttle('notifications', 120);

$pdo = db();
$acc = current_account($pdo);

$limit = (int)($_GET['limit'] ?? 20);
if ($limit < 1 || $limit > 50) $limit = 20;

ok([
    'authenticated' => true,
    'available'     => notes_available($pdo),
    'unread'        => notes_unread($pdo, (int)$acc['id']),
    'notifications' => notes_list($pdo, (int)$acc['id'], $limit),
]);
