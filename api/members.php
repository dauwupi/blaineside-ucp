<?php
/**
 * GET /api/members.php?q=&page=
 *
 * The account list behind Group Management. Management and above only — it
 * is a directory of every account with its group, which is not something to
 * hand out more widely than the tool that needs it.
 *
 * Deliberately narrow: name, group, when they joined, when they were last
 * seen. No email, no Discord, no IP. Changing someone's group doesn't
 * require knowing how to contact them.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_groups.php';

$pdo = db();
$acc = current_account($pdo);
require_group_manager($acc);

$actorRank = (int)$acc['admin_rank'];
$perPage   = 12;
$page      = max(1, (int)($_GET['page'] ?? 1));
$q         = trim((string)($_GET['q'] ?? ''));

$where  = "status <> 'pending'";
$params = [];
if ($q !== '') {
    $where .= ' AND username_lower LIKE ?';
    $params[] = '%' . strtolower($q) . '%';
}

$countSt = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts WHERE $where");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;

$st = $pdo->prepare(
    "SELECT id, username, admin_rank, status, created_at, last_login
       FROM ucp_accounts
      WHERE $where
      ORDER BY admin_rank DESC, username_lower ASC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$st->execute($params);

$out = [];
foreach ($st->fetchAll() as $r) {
    $target = ['id' => $r['id'], 'admin_rank' => $r['admin_rank']];
    $block  = groups_block_reason($acc, $target);
    $out[] = [
        'id'         => (int)$r['id'],
        'name'       => (string)$r['username'],
        'rank'       => (int)$r['admin_rank'],
        'role'       => rank_name((int)$r['admin_rank']),
        'status'     => (string)$r['status'],
        'created_at' => $r['created_at'],
        'last_login' => $r['last_login'],
        'self'       => (int)$r['id'] === (int)$acc['id'],
        'editable'   => $block === null,
        'blocked_by' => $block,
    ];
}

// The ladder, so the page labels groups the same way the server does.
$ranks = [];
foreach (RANKS as $n => $label) $ranks[] = ['rank' => $n, 'name' => $label];

ok([
    'members'    => $out,
    'page'       => $page,
    'pages'      => $pages,
    'total'      => $total,
    'ranks'      => $ranks,
    'assignable' => array_values(groups_assignable($actorRank)),
    'you'        => ['id' => (int)$acc['id'], 'rank' => $actorRank],
]);
