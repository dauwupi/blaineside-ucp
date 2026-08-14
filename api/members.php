<?php
/**
 * GET /api/members.php?group=&q=&page=
 *
 * The account list behind Group Management, plus a count for every group so
 * the page can show the ladder at a glance.
 *
 * Nothing is listed until a group is chosen or a search is typed. A UCP with
 * forty thousand accounts has no business dumping page one of them at
 * somebody who came here to promote one person — and the counts are the part
 * that is actually useful at rest.
 *
 * Management and above only. Deliberately narrow: name, group, when they
 * joined, when they were last seen, whether two-step is on. No email, no
 * Discord, no IP — changing someone's group doesn't require knowing how to
 * contact them.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_groups.php';
require_once __DIR__ . '/_teams.php';

$pdo = db();
$acc = current_account($pdo);
require_group_manager($acc);

$actorRank = (int)$acc['admin_rank'];
$perPage   = 12;
$page      = max(1, (int)($_GET['page'] ?? 1));
$q         = trim((string)($_GET['q'] ?? ''));
$group     = isset($_GET['group']) && $_GET['group'] !== '' ? (int)$_GET['group'] : null;

// ---- The ladder, with a headcount against each rung ------------------------
$counts = [];
foreach (RANKS as $n => $label) $counts[$n] = 0;
$cs = $pdo->query("SELECT admin_rank, COUNT(*) c FROM ucp_accounts WHERE status <> 'pending' GROUP BY admin_rank");
foreach ($cs->fetchAll() as $r) $counts[(int)$r['admin_rank']] = (int)$r['c'];

$ranks = [];
foreach (RANKS as $n => $label) {
    $ranks[] = ['rank' => $n, 'name' => $label, 'count' => $counts[$n] ?? 0];
}

$base = [
    'ranks'      => $ranks,
    'assignable' => array_values(groups_assignable($actorRank)),
    'you'        => ['id' => (int)$acc['id'], 'rank' => $actorRank],
    'per_page'   => $perPage,
    'total_all'  => array_sum($counts),

    // The sub-group registry, and whether the migration that stores them has
    // been run. The page builds its toggles from this; a UCP that hasn't run
    // docs/migration-subgroups.sql shows the section as unavailable rather
    // than offering switches that would silently fail.
    'teams'      => teams_registry(),
    'teams_ok'   => teams_available($pdo),
    'team_band'  => ['min' => BS_TEAM_MIN_RANK, 'max' => BS_TEAM_MAX_RANK,
                     'label' => rank_name(BS_TEAM_MIN_RANK) . ' – ' . rank_name(BS_TEAM_MAX_RANK)],
];

// ---- Nothing asked for, nothing listed -------------------------------------
if ($group === null && $q === '') {
    ok($base + ['members' => [], 'page' => 1, 'pages' => 1, 'total' => 0, 'listed' => false]);
}

$where  = "status <> 'pending'";
$params = [];
if ($group !== null) {
    $where .= ' AND admin_rank = ?';
    $params[] = $group;
}
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
    "SELECT id, username, admin_rank, status, created_at, last_login,
            totp_enabled, forum_member_id
       FROM ucp_accounts
      WHERE $where
      ORDER BY admin_rank DESC, username_lower ASC
      LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$st->execute($params);
$rows = $st->fetchAll();

// One query for the page, not one per row.
$teamsBy = teams_map($pdo, array_column($rows, 'id'));

$out = [];
foreach ($rows as $r) {
    $block = groups_block_reason($acc, ['id' => $r['id'], 'admin_rank' => $r['admin_rank']]);
    $out[] = [
        'id'         => (int)$r['id'],
        'name'       => (string)$r['username'],
        'rank'       => (int)$r['admin_rank'],
        'role'       => rank_name((int)$r['admin_rank']),
        'status'     => (string)$r['status'],
        'created_at' => $r['created_at'],
        'last_login' => $r['last_login'],
        'twofa'      => !empty($r['totp_enabled']),
        'forum'      => $r['forum_member_id'] !== null,
        'self'       => (int)$r['id'] === (int)$acc['id'],
        'editable'   => $block === null,
        'blocked_by' => $block,

        // Sub-groups: what they hold, and whether their rank allows any.
        'teams'          => $teamsBy[(int)$r['id']] ?? [],
        'team_eligible'  => team_eligible((int)$r['admin_rank']),
        'team_why'       => team_eligible((int)$r['admin_rank'])
                            ? null : team_ineligible_reason((int)$r['admin_rank']),
    ];
}

ok($base + [
    'members' => $out,
    'page'    => $page,
    'pages'   => $pages,
    'total'   => $total,
    'listed'  => true,
    'group'   => $group,
    'q'       => $q,
    'from'    => $total ? (($page - 1) * $perPage) + 1 : 0,
    'to'      => min($page * $perPage, $total),
]);
