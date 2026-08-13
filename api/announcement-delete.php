<?php
/**
 * POST /api/announcement-delete.php
 * Body: { id }
 *
 * Deleting the live announcement takes it off the dashboard as a side
 * effect, which is the obvious reading of "delete" — no confirmation dance
 * about what happens to the strip.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_announcements.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('announce_delete', 30);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$id = (int)(read_input()['id'] ?? 0);

$st = $pdo->prepare('SELECT id, active FROM ucp_announcements WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) fail('That announcement no longer exists.', 404);

$pdo->prepare('DELETE FROM ucp_announcements WHERE id = ?')->execute([$id]);

ok([
    'id'      => $id,
    'message' => $row['active'] ? 'Announcement deleted and removed from the dashboard.'
                                : 'Announcement deleted.',
]);
