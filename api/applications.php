<?php
/**
 * GET /api/applications.php?tab=outstanding&page=1&per=10&filter=all
 *
 * The Application Panel's two lists and the four counters above them.
 *
 * Two tabs, not two cards: outstanding work and the archive answer
 * different questions and are never read at the same time, so they share
 * one table and swap their columns.
 *
 * The counters are all-time on purpose. "Today" flatters a quiet day and
 * punishes a busy one, and the number a Support Staff member actually
 * wants is how much of the whole pile is theirs.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_applications.php';

throttle('applications_list', 60);

$pdo = db();
$acc = current_account($pdo);

if (!app_may_panel($acc)) {
    json_out(['ok' => false, 'error' => app_panel_reason()], 403);
}
if (!applications_available($pdo)) {
    json_out(['ok' => false, 'error' => applications_missing_reason()], 409);
}

$me     = (int)$acc['id'];
$tab    = (string)($_GET['tab'] ?? 'outstanding');
$filter = (string)($_GET['filter'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = (int)($_GET['per'] ?? 10);
if (!in_array($per, [10, 25, 50, 100], true)) $per = 10;
if ($tab !== 'archive') $tab = 'outstanding';

$where  = [];
$params = [];

if ($tab === 'outstanding') {
    $where[] = "a.status = 'pending'";
    if ($filter === 'unclaimed') $where[] = 'a.claimed_by IS NULL';
    if ($filter === 'mine')      { $where[] = 'a.claimed_by = ?'; $params[] = $me; }
} else {
    $where[] = "a.status IN ('passed','denied')";
    if ($filter === 'passed') $where[] = "a.status = 'passed'";
    if ($filter === 'denied') $where[] = "a.status = 'denied'";
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $where[]  = '(u.username LIKE ? OR a.id = ?)';
    $params[] = '%' . $q . '%';
    $params[] = (int)$q;
}

$sql = 'FROM ucp_applications a JOIN ucp_accounts u ON u.id = a.account_id
         WHERE ' . implode(' AND ', $where);

$st = $pdo->prepare('SELECT COUNT(*) ' . $sql);
$st->execute($params);
$total = (int)$st->fetchColumn();

$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;

$order = $tab === 'outstanding'
    ? 'a.submitted_at ASC'      // oldest first: it is a queue
    : 'a.decided_at DESC';      // newest first: it is a record

$st = $pdo->prepare(
    'SELECT a.*, u.username ' . $sql . ' ORDER BY ' . $order .
    ' LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per)
);
$st->execute($params);

$rows = [];
foreach ($st->fetchAll() as $r) {
    $row = app_row_out($r);
    $row['player'] = ['id' => (int)$r['account_id'], 'name' => $r['username']];
    $row['mine']   = $r['claimed_by'] !== null && (int)$r['claimed_by'] === $me;
    $row['may']    = app_may_act($pdo, $acc, $r);
    $rows[] = $row;
}

/* ---- the counters ---- */
$counts = ['waiting' => 0, 'claimed' => 0, 'passed' => 0, 'denied' => 0, 'mine' => 0, 'oldest' => null];
foreach ($pdo->query(
    "SELECT status, COUNT(*) c FROM ucp_applications
      WHERE status <> 'draft' GROUP BY status")->fetchAll() as $r) {
    if ($r['status'] === 'pending') $counts['waiting'] = (int)$r['c'];
    if ($r['status'] === 'passed')  $counts['passed']  = (int)$r['c'];
    if ($r['status'] === 'denied')  $counts['denied']  = (int)$r['c'];
}
$counts['claimed'] = (int)$pdo->query(
    "SELECT COUNT(*) FROM ucp_applications WHERE status = 'pending' AND claimed_by IS NOT NULL"
)->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) FROM ucp_applications WHERE decided_by = ?");
$st->execute([$me]);
$counts['mine'] = (int)$st->fetchColumn();
$oldest = $pdo->query(
    "SELECT MIN(submitted_at) FROM ucp_applications WHERE status = 'pending'"
)->fetchColumn();
$counts['oldest'] = $oldest !== null && $oldest !== false ? (int)$oldest : null;

$decided = $counts['passed'] + $counts['denied'];
$counts['pass_pct'] = $decided ? (int)round($counts['passed'] / $decided * 100) : 0;
$counts['deny_pct'] = $decided ? 100 - $counts['pass_pct'] : 0;
$counts['mine_pct'] = $decided ? (int)round($counts['mine'] / $decided * 100) : 0;
$counts['decided']  = $decided;

ok([
    'tab'    => $tab,
    'filter' => $filter,
    'rows'   => $rows,
    'counts' => $counts,
    'page'   => $page,
    'pages'  => $pages,
    'per'    => $per,
    'total'  => $total,
    'me'     => ['id' => $me, 'name' => $acc['username'], 'rank' => (int)$acc['admin_rank']],
]);
