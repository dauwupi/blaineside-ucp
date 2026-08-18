<?php
/**
 * GET /api/store-tickets.php?scope=mine&status=all&page=1
 *
 * The ticket list, paged the same way the administrative record is —
 * ten to a page, a window of three around the current one, arrows either
 * side. Paging that behaves differently on different pages is a small
 * cruelty, so this borrows the record's shape exactly.
 *
 * `scope=all` is Management only, and is refused rather than quietly
 * narrowed for anybody else: silently showing somebody their own tickets
 * when they asked for everyone's would make a permission look like an
 * empty queue.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_store.php';

throttle('store_tickets', 60);

$pdo = db();
$acc = current_account($pdo);

if (!store_available($pdo)) {
    json_out(['ok' => true, 'available' => false, 'why' => store_missing_reason(),
              'rows' => [], 'page' => 1, 'pages' => 1, 'total' => 0,
              'staff' => store_is_staff($acc)], 200);
}

$staff  = store_is_staff($acc);
$scope  = (string)($_GET['scope'] ?? 'mine');
$status = (string)($_GET['status'] ?? 'all');
$page   = max(1, (int)($_GET['page'] ?? 1));
$term   = trim((string)($_GET['q'] ?? ''));
$sort   = (string)($_GET['sort'] ?? 'newest');

if ($scope === 'all' && !$staff) {
    json_out(['ok' => false, 'error' => 'Only Management can read everybody\'s tickets.'], 403);
}

$where  = [];
$params = [];

if ($scope !== 'all') {
    $where[]  = 't.account_id = ?';
    $params[] = (int)$acc['id'];
}
if (in_array($status, STORE_TICKET_STATUSES, true)) {
    $where[]  = 't.status = ?';
    $params[] = $status;
} elseif ($status === 'live') {
    $where[] = "t.status <> 'closed'";
}

/* One box searching three things, because somebody working the queue has
   one of three things to hand: a name, an order reference, or a phrase
   they remember from the ticket. */
if ($term !== '') {
    $where[]  = '(u.username LIKE ? OR t.order_ref LIKE ? OR t.subject LIKE ?)';
    $like     = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql = 'FROM ucp_store_tickets t JOIN ucp_accounts u ON u.id = t.account_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

$st = $pdo->prepare('SELECT COUNT(*) ' . $sql);
$st->execute($params);
$total = (int)$st->fetchColumn();

$per   = BS_STORE_PER_PAGE;
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) $page = $pages;

/* Anything waiting on a reply floats above anything closed, and within
   each the most recently touched first — the order somebody working the
   queue actually wants.

   `t.id DESC` last is not decoration. Several tickets routinely share an
   updated_at to the second, and an ORDER BY with ties is free to return
   them in a different order for each page — which showed up as the same
   ticket appearing on page one AND page two while another never appeared
   at all. The id breaks every tie, so the sort is total and paging is
   stable. */
$st = $pdo->prepare(
    'SELECT t.*, u.username ' . $sql .
    " ORDER BY FIELD(t.status, 'open', 'answered', 'closed'), " .
    ($sort === 'oldest' ? 't.created_at ASC, t.id ASC' : 't.updated_at DESC, t.id DESC') .
    ' LIMIT ' . (int)$per . ' OFFSET ' . (int)(($page - 1) * $per)
);
$st->execute($params);

$rows = array_map(function ($t) use ($acc) {
    return store_ticket_out($t, $acc);
}, $st->fetchAll());

/* Counts for the tab badge and the filter, over the same scope. */
$cs = ['open' => 0, 'answered' => 0, 'closed' => 0];
$q  = 'SELECT status, COUNT(*) c FROM ucp_store_tickets'
    . ($scope !== 'all' ? ' WHERE account_id = ' . (int)$acc['id'] : '')
    . ' GROUP BY status';
foreach ($pdo->query($q)->fetchAll() as $r) {
    if (isset($cs[$r['status']])) $cs[$r['status']] = (int)$r['c'];
}

ok([
    'available' => true,
    'rows'   => $rows,
    'page'   => $page,
    'pages'  => $pages,
    'per'    => $per,
    'total'  => $total,
    'counts' => $cs,
    'scope'  => $scope,
    'status' => $status,
    'q'      => $term,
    'sort'   => $sort,
    'categories' => STORE_CATEGORIES,
    'staff'  => $staff,
    'me'     => ['id' => (int)$acc['id'], 'name' => $acc['username']],
]);
