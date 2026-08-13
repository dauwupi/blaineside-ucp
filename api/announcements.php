<?php
/**
 * GET /api/announcements.php
 *   (no scope)        the live announcement, or null — any signed-in player
 *   ?scope=all&page=N the full list for the management page (rank 8+)
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_announcements.php';

$pdo  = db();
$acc  = current_account($pdo);
$rank = (int)$acc['admin_rank'];

if (($_GET['scope'] ?? '') !== 'all') {
    $st = $pdo->query('SELECT * FROM ucp_announcements WHERE active = 1 ORDER BY created_at DESC LIMIT 1');
    $row = $st->fetch();

    ok([
        'announcement' => $row ? announcement_out($row) : null,
        'may_manage'   => bulletin_may_manage($rank),
    ]);
}

require_bulletin_manager($acc);

$page  = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$pdo->query('SELECT COUNT(*) FROM ucp_announcements')->fetchColumn();
$pages = max(1, (int)ceil($total / BS_ANNOUNCE_PER_PAGE));
if ($page > $pages) $page = $pages;

$st = $pdo->prepare(
    'SELECT * FROM ucp_announcements
      ORDER BY active DESC, created_at DESC, id DESC
      LIMIT ' . BS_ANNOUNCE_PER_PAGE . ' OFFSET ' . (($page - 1) * BS_ANNOUNCE_PER_PAGE)
);
$st->execute();

$out = [];
foreach ($st->fetchAll() as $r) $out[] = announcement_out($r);

ok([
    'announcements' => $out,
    'page'  => $page,
    'pages' => $pages,
    'total' => $total,
]);
