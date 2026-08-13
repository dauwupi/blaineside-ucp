<?php
/**
 * POST /api/bulletin-delete.php
 * Body: { id }
 *
 * Removes a bulletin outright. There is no soft delete: a bulletin is a
 * notice board post, and an unpublished one already has a state — off the
 * dashboard — so a hidden third state would only be somewhere for mistakes
 * to accumulate.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_bulletins.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('bulletin_delete', 30);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$id = (int)(read_input()['id'] ?? 0);
if ($id <= 0) fail('That bulletin no longer exists.', 404);

$st = $pdo->prepare('SELECT title FROM ucp_bulletins WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) fail('That bulletin no longer exists.', 404);

$pdo->prepare('DELETE FROM ucp_bulletins WHERE id = ?')->execute([$id]);

ok(['id' => $id, 'message' => 'Bulletin deleted.']);
