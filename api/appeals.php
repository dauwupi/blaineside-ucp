<?php
/**
 * GET /api/appeals.php?tab=pending&q=&page=1&per=10
 *
 * The appeal queue. Support Staff and above.
 *
 * Five tabs, and the counts come back with every response rather than from a
 * separate call: the numbers on the tabs are the reason anyone opens this
 * page, and a tab bar that renders before its counts arrive flashes zeros at
 * the person who came to find out whether there is work.
 *
 * Sorting is fixed rather than user-controlled. The queue is worked oldest
 * first, and a sort control is an invitation to work the easy ones.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_appeals.php';

throttle('appeals_list', 60);

$pdo = db();
$acc = current_account($pdo);

if (!appeal_is_staff($acc)) {
    json_out([
        'ok'    => false,
        'error' => 'The appeal queue is for ' . rank_name(BS_APPEAL_STAFF_RANK) . ' and above.',
    ], 403);
}

if (!appeals_available($pdo)) {
    json_out(['ok' => false, 'error' => appeals_missing_reason()], 409);
}

$me   = (int)$acc['id'];
$tab  = (string)($_GET['tab'] ?? 'pending');
$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = (int)($_GET['per'] ?? 10);
if (!in_array($per, [10, 25, 50, 100], true)) $per = 10;

/**
 * Each tab is a WHERE fragment. Written out rather than composed from flags
 * so the SQL for "not assigned" is readable next to what the tab is called.
 */
$TABS = [
    'all'      => ['label' => 'All',                 'where' => '1 = 1'],
    'pending'  => ['label' => 'All pending appeals', 'where' => "a.status = 'pending'"],
    'unassigned' => ['label' => 'Not assigned',
                     'where' => "a.status = 'pending' AND a.handler_id IS NULL"],
    'mine'     => ['label' => 'Assigned to me',
                   'where' => "a.status = 'pending' AND a.handler_id = " . $me],
    'handled'  => ['label' => 'Handled',             'where' => "a.status <> 'pending'"],
];
if (!isset($TABS[$tab])) $tab = 'pending';

/* The filter box searches the appellant and the handler — the two names on
   the row. Escaped with ESCAPE '|' rather than a backslash: a backslash is
   one character to MySQL and two to SQLite, and this runs against both. */
$args  = [];
$search = '';
if ($q !== '') {
    $needle = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower($q)) . '%';
    $search = " AND (LOWER(u.username) LIKE ? ESCAPE '|'
                     OR LOWER(COALESCE(a.handler_name, '')) LIKE ? ESCAPE '|')";
    $args = [$needle, $needle];
}

$base = ' FROM ucp_appeals a LEFT JOIN ucp_accounts u ON u.id = a.account_id
          WHERE ' . $TABS[$tab]['where'] . $search;

$cnt = $pdo->prepare('SELECT COUNT(*)' . $base);
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $per;

$st = $pdo->prepare(
    'SELECT a.id, a.status, a.handler_name, a.created_at, a.updated_at,
            a.account_id, u.username' . $base .
    ' ORDER BY a.created_at ASC, a.id ASC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset
);
$st->execute($args);

$rows = array_map(function ($r) {
    return [
        'id'      => (int)$r['id'],
        // An account deleted since is not an error — the appeal is still a
        // record of what happened, and the table says so in words.
        'user'    => $r['username'] !== null ? (string)$r['username'] : null,
        'user_id' => (int)$r['account_id'],
        'admin'   => $r['handler_name'] ?: null,
        'status'  => (string)$r['status'],
        'created' => (int)$r['created_at'],
        'updated' => (int)$r['updated_at'],
    ];
}, $st->fetchAll());

/* Counts for every tab, unaffected by the filter box. A count that moves
   when you type in the filter stops being the answer to "how much is left". */
$counts = [];
foreach ($TABS as $k => $t) {
    $c = $pdo->query('SELECT COUNT(*) FROM ucp_appeals a WHERE ' . $t['where']);
    $counts[$k] = (int)$c->fetchColumn();
}

ok([
    'authenticated' => true,
    'tab'    => $tab,
    'tabs'   => array_map(function ($k, $t) use ($counts) {
        return ['key' => $k, 'label' => $t['label'], 'count' => $counts[$k]];
    }, array_keys($TABS), array_values($TABS)),
    'q'      => $q,
    'page'   => $page,
    'pages'  => $pages,
    'per'    => $per,
    'total'  => $total,
    'appeals'=> $rows,
]);
