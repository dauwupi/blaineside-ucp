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
require_once __DIR__ . '/_sessions.php';

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

    /* Members can't be browsed — see below. The page needs to know so it can
       say why rather than showing an empty list. */
    'browse_min' => 1,
    'team_band'  => ['min' => BS_TEAM_MIN_RANK, 'max' => BS_TEAM_MAX_RANK,
                     'label' => rank_name(BS_TEAM_MIN_RANK) . ' – ' . rank_name(BS_TEAM_MAX_RANK)],
];

// ---- Nothing asked for, nothing listed -------------------------------------
if ($group === null && $q === '') {
    ok($base + ['members' => [], 'page' => 1, 'pages' => 1, 'total' => 0, 'listed' => false]);
}

/* ---- Members are found, not browsed ---------------------------------------
 *
 * Every account that isn't staff sits in group 0, which on a community of any
 * size is effectively the whole database. Listing it would page through tens
 * of thousands of rows to answer a question nobody asked — and the one time
 * somebody does want a specific player, they already know the name.
 *
 * So Member is the one group you can't open. You can still find anyone in it,
 * by typing their name in full. Every other group is small enough to browse.
 */
if ($group === 0 && $q === '') {
    ok($base + [
        'members' => [], 'page' => 1, 'pages' => 1, 'total' => 0,
        'listed'  => false,
        'reason'  => 'Members can\'t be listed — there are far too many. Type the full UCP name '
                   . 'in the search box to find one.',
    ]);
}

/* Unverified sign-ups are left out of a browse and found by a search.
 *
 * Browsing a group is asking "who is in it", and somebody who never answered
 * their confirmation email isn't in anything yet — mostly they are bot-filled
 * forms. But "did their verification ever come through?" is a real question
 * with a real answer, so an exact-name search finds them, and the row says
 * Pending email. */
$where  = $q === '' ? "status <> 'pending'" : '1 = 1';
$params = [];
if ($group !== null) {
    $where .= ' AND admin_rank = ?';
    $params[] = $group;
}
if ($q !== '') {
    /* Partial matching for staff, exact for Members.
     *
     * Typing "a" should not return four thousand players. Staff groups are
     * small enough that a partial name is a convenience; group 0 is not, so
     * it answers only to the whole name. */
    $where .= ' AND ((admin_rank >= 1 AND username_lower LIKE ?) OR (admin_rank = 0 AND username_lower = ?))';
    $params[] = '%' . strtolower($q) . '%';
    $params[] = strtolower($q);
}

$countSt = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts a WHERE $where");
$countSt->execute($params);
$total = (int)$countSt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
if ($page > $pages) $page = $pages;

/* Last ACTIVE, not last signed in.
 *
 * ucp_sessions.last_seen is stamped every minute while somebody is using the
 * UCP, whether they typed a password today or came back on a remember-me
 * cookie from three weeks ago. last_login only answers the second question,
 * and for anyone with Remember Me on it can be months stale while they are
 * on the site daily. The correlated subquery is portable and hits an indexed
 * column; the fallback is for accounts that predate session tracking. */
$activity = sessions_available($pdo)
    ? '(SELECT MAX(s.last_seen) FROM ucp_sessions s WHERE s.account_id = a.id)'
    : 'NULL';

$st = $pdo->prepare(
    "SELECT a.id, a.username, a.admin_rank, a.status, a.created_at, a.last_login,
            a.totp_enabled, a.forum_member_id, a.discord, a.discord_username,
            $activity AS last_seen
       FROM ucp_accounts a
      WHERE $where
      ORDER BY a.admin_rank DESC, a.username_lower ASC
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
        'discord'    => !empty($r['discord_username']),

        /* Unix seconds. Null means they have never used the UCP at all — a
           different thing from "we don't know", which is why last_login is
           only a fallback and not merged in silently. */
        'last_seen'  => $r['last_seen'] !== null ? (int)$r['last_seen']
                        : ($r['last_login'] ? strtotime((string)$r['last_login']) : null),
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
