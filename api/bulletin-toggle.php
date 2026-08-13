<?php
/**
 * POST /api/bulletin-toggle.php
 * Body: { id, on }
 *
 * Puts a bulletin on the dashboard, or takes it off.
 *
 * The five-slot limit is enforced here rather than in the page, because the
 * page's idea of how full the dashboard is comes from a listing that may be
 * a minute old and may predate what another manager just did.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_bulletins.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('bulletin_toggle', 60);

$pdo = db();
$acc = current_account($pdo);
require_bulletin_manager($acc);

$in = read_input();
$id = (int)($in['id'] ?? 0);
$on = !empty($in['on']);

$st = $pdo->prepare('SELECT id, on_dashboard FROM ucp_bulletins WHERE id = ? LIMIT 1');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) fail('That bulletin no longer exists.', 404);

$shown = (int)$pdo->query('SELECT COUNT(*) FROM ucp_bulletins WHERE on_dashboard = 1')->fetchColumn();

if ($on && !$row['on_dashboard'] && $shown >= BS_BULLETIN_MAX_SHOWN) {
    json_out([
        'ok'    => false,
        'full'  => true,
        'shown' => $shown,
        'error' => 'The dashboard is full — turn one off first.',
    ], 409);
}

$pdo->prepare('UPDATE ucp_bulletins SET on_dashboard = ?, updated_at = ? WHERE id = ?')
    ->execute([$on ? 1 : 0, time(), $id]);

ok([
    'id'      => $id,
    'shown'   => $on,
    'count'   => $on ? $shown + ($row['on_dashboard'] ? 0 : 1) : $shown - 1,
    'message' => $on ? 'Added to dashboard.' : 'Removed from dashboard.',
]);
