<?php
/**
 * GET /api/bulletins.php
 *   ?scope=dashboard        the up-to-5 rotating on the dashboard (any player)
 *   ?scope=all&page=N       the full list for the management page (rank 8+)
 *   ?id=N                   one bulletin in full, image included (rank 8+)
 *
 * Reading the dashboard set is open to every signed-in player — it is the
 * news, it is meant to be read. Everything else is Management and above,
 * because the management listing exposes unpublished drafts and who wrote
 * what.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_bulletins.php';

$pdo   = db();
$acc   = current_account($pdo);
$rank  = (int)$acc['admin_rank'];
$scope = (string)($_GET['scope'] ?? 'dashboard');

// ---- One bulletin, in full (the editor opening an existing post) ----------
if (isset($_GET['id'])) {
    require_bulletin_manager($acc);

    $st = $pdo->prepare('SELECT * FROM ucp_bulletins WHERE id = ? LIMIT 1');
    $st->execute([(int)$_GET['id']]);
    $row = $st->fetch();
    if (!$row) fail('That bulletin no longer exists.', 404);

    ok(['bulletin' => bulletin_out($row, true)]);
}

// ---- The dashboard set ----------------------------------------------------
if ($scope === 'dashboard') {
    $st = $pdo->prepare(
        'SELECT * FROM ucp_bulletins
          WHERE on_dashboard = 1
          ORDER BY created_at DESC, id DESC
          LIMIT ' . BS_BULLETIN_MAX_SHOWN
    );
    $st->execute();

    $out = [];
    foreach ($st->fetchAll() as $r) $out[] = bulletin_out($r, true);

    ok([
        'bulletins' => $out,
        // The dashboard uses this to decide whether to offer the management
        // link at all, so the answer comes from the server rather than from
        // whatever the page happens to believe about the visitor.
        'may_manage' => bulletin_may_manage($rank),
    ]);
}

// ---- The management listing ----------------------------------------------
require_bulletin_manager($acc);

$page  = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$pdo->query('SELECT COUNT(*) FROM ucp_bulletins')->fetchColumn();
$pages = max(1, (int)ceil($total / BS_BULLETIN_PER_PAGE));
if ($page > $pages) $page = $pages;

$st = $pdo->prepare(
    'SELECT * FROM ucp_bulletins
      ORDER BY created_at DESC, id DESC
      LIMIT ' . BS_BULLETIN_PER_PAGE . ' OFFSET ' . (($page - 1) * BS_BULLETIN_PER_PAGE)
);
$st->execute();

$out = [];
foreach ($st->fetchAll() as $r) $out[] = bulletin_out($r, false);

ok([
    'bulletins' => $out,
    'page'      => $page,
    'pages'     => $pages,
    'total'     => $total,
    'shown'     => (int)$pdo->query('SELECT COUNT(*) FROM ucp_bulletins WHERE on_dashboard = 1')->fetchColumn(),
    'max_shown' => BS_BULLETIN_MAX_SHOWN,
    'per_page'  => BS_BULLETIN_PER_PAGE,
]);
