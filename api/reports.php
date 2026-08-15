<?php
/**
 * GET /api/reports.php?tab=pending&q=&page=1&per=10
 *
 * The staff report queue. Management, Founder, or Staff Management.
 *
 * Tabs mirror how the work actually arrives: everything untriaged, then
 * everything unallocated, then what is mine. "Untriaged" comes first
 * because the information panel promises a category within 24-48 hours,
 * and a queue whose first tab is not the thing that was promised makes the
 * promise somebody's private responsibility.
 *
 * Reports naming the caller are removed from every tab and from every
 * count. Not greyed out, not shown as "restricted" — a row that says a
 * report about you exists is most of what the report told anyone.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_reports.php';

throttle('reports_list', 60);

$pdo = db();
$acc = current_account($pdo);

if (!report_may_panel($pdo, $acc)) {
    json_out(['ok' => false, 'error' => report_panel_reason()], 403);
}
if (!reports_available($pdo)) {
    json_out(['ok' => false, 'error' => reports_missing_reason()], 409);
}

$me   = (int)$acc['id'];
$tab  = (string)($_GET['tab'] ?? 'untriaged');
$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = (int)($_GET['per'] ?? 10);
if (!in_array($per, [10, 25, 50, 100], true)) $per = 10;

$TABS = [
    'all'        => ['label' => 'All',        'where' => '1 = 1'],
    'untriaged'  => ['label' => 'Untriaged',
                     'where' => "r.status = 'pending' AND r.category IS NULL"],
    'pending'    => ['label' => 'All open',   'where' => "r.status = 'pending'"],
    'unallocated'=> ['label' => 'Not allocated',
                     'where' => "r.status = 'pending' AND r.handler_id IS NULL"],
    'mine'       => ['label' => 'Allocated to me',
                     'where' => "r.status = 'pending' AND r.handler_id = " . $me],
    'concluded'  => ['label' => 'Concluded',  'where' => "r.status <> 'pending'"],
];
if (!isset($TABS[$tab])) $tab = 'untriaged';

/* Rule 2, in SQL, and only for the people it applies to: a Staff Management
   holder does not see reports naming them, and Management and Founders do —
   see report_subject_blind(). NOT EXISTS rather than a LEFT JOIN with a NULL
   test, because a report can name several people and a join would multiply
   the row and then need a DISTINCT to undo itself. */
$blind = report_subject_blind($pdo, $acc);
$hide  = $blind
    ? ' AND NOT EXISTS (SELECT 1 FROM ucp_report_staff rs
                         WHERE rs.report_id = r.id AND rs.account_id = ' . $me . ')'
    : '';

/* The filter box searches the title, the reporter, and the staff named.
   Escaped with ESCAPE '|' rather than a backslash: a backslash is one
   character to MySQL and two to SQLite, and this runs against both. */
$args = [];
$search = '';
if ($q !== '') {
    $needle = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], mb_strtolower($q)) . '%';
    $search = " AND (LOWER(r.title) LIKE ? ESCAPE '|'
                     OR LOWER(COALESCE(u.username, '')) LIKE ? ESCAPE '|'
                     OR EXISTS (SELECT 1 FROM ucp_report_staff rs2
                                 WHERE rs2.report_id = r.id
                                   AND LOWER(rs2.name) LIKE ? ESCAPE '|'))";
    $args = [$needle, $needle, $needle];
}

$base = ' FROM ucp_reports r LEFT JOIN ucp_accounts u ON u.id = r.account_id
          WHERE ' . $TABS[$tab]['where'] . $hide . $search;

$cnt = $pdo->prepare('SELECT COUNT(*)' . $base);
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;
$offset = ($page - 1) * $per;

/* Oldest first, and not user-sortable. The queue is worked in the order it
   arrived; a sort control is an invitation to work the easy ones. */
$st = $pdo->prepare(
    'SELECT r.id, r.title, r.status, r.category, r.outcome, r.handler_name,
            r.created_at, r.updated_at, r.account_id, u.username' . $base .
    ' ORDER BY r.created_at ASC, r.id ASC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset
);
$st->execute($args);

$rows = array_map(function ($r) use ($pdo) {
    return [
        'id'      => (int)$r['id'],
        'title'   => (string)$r['title'],
        // An account deleted since is not an error — the report is still a
        // record of what happened, and the table says so in words.
        'user'    => $r['username'] !== null ? (string)$r['username'] : null,
        'user_id' => (int)$r['account_id'],
        'staff'   => array_map(function ($s) { return $s['name']; },
                     report_subjects($pdo, (int)$r['id'])),
        'handler' => $r['handler_name'] ?: null,
        'status'  => (string)$r['status'],
        'category'=> report_category_label($r['category']),
        'outcome' => report_outcome_label($r['outcome']),
        'created' => (int)$r['created_at'],
        'updated' => (int)$r['updated_at'],
        /* Only ever true for Management and Founders — everybody else who
           could be named never sees the row. The queue marks it so a
           conflict of interest is visible before the report is opened
           rather than after. */
        'names_me'=> in_array($me, report_subject_ids($pdo, (int)$r['id']), true),
    ];
}, $st->fetchAll());

/* Counts for every tab, unaffected by the filter box — a count that moves
   when you type stops being the answer to "how much is left". The subject
   rule still applies: these are counts of what this person can open. */
$counts = [];
foreach ($TABS as $k => $t) {
    $c = $pdo->query('SELECT COUNT(*) FROM ucp_reports r WHERE ' . $t['where'] . $hide);
    $counts[$k] = (int)$c->fetchColumn();
}

ok([
    'authenticated' => true,
    'tab'   => $tab,
    'tabs'  => array_map(function ($k, $t) use ($counts) {
        return ['key' => $k, 'label' => $t['label'], 'count' => $counts[$k]];
    }, array_keys($TABS), array_values($TABS)),
    'q'     => $q,
    'page'  => $page,
    'pages' => $pages,
    'per'   => $per,
    'total' => $total,
    'blind' => $blind,
    'reports' => $rows,
]);
