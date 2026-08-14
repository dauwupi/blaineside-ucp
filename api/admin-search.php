<?php
/**
 * GET /api/admin-search.php
 *
 * Administrative Search. Every criterion the form offers arrives as its own
 * parameter and they combine with AND:
 *
 *   ?tab=user&ucp=jon&group=3&twofa=0&seen_before=2026-01-01
 *
 *   ?tab=      user | property | vehicle   (default user)
 *   ?page=     1-based
 *
 * With no criteria it returns the registry and nothing else, which is what
 * the page loads with: the form is built from the server's list, so a field
 * with no backend behind it can't be offered as though it works.
 *
 * Trainee Admin and above. The rank is checked here, not just in the menu.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_ips.php';
require_once __DIR__ . '/_teams.php';
require_once __DIR__ . '/_admin.php';

throttle('admin-search', 40);

$pdo = db();
$acc = current_account($pdo);
require_admin_searcher($acc);

$tabs = admin_search_tabs();
$key  = trim((string)($_GET['tab'] ?? 'user'));
$page = max(1, (int)($_GET['page'] ?? 1));

$tab = admin_search_tab($key);
if ($tab === null) {
    json_out(['ok' => false, 'error' => 'That isn\'t a lookup that exists.'], 400);
}

/** Everything the form could have sent, as a plain map. */
$in = [];
foreach ($tab['fields'] as $f) {
    if (isset($_GET[$f['key']])) $in[$f['key']] = (string)$_GET[$f['key']];
}

$base = [
    'tabs' => $tabs,
    'tab'  => $key,
    'searched' => false,
    'results'  => [],
    'total'    => 0,
];

// ---- A lookup with nothing behind it explains itself ------------------------
if (!$tab['available']) {
    ok($base + ['blocked' => $tab['why']]);
}

// ---- Nothing filled in -----------------------------------------------------
$canSeeStaff = admin_can_see_staff($pdo, $acc);
list($where, $args, $used, $note) = admin_build_user_query($pdo, $in);

if (!$used) {
    ok($base + ['note' => $note]);
}

// A criterion we couldn't evaluate (the forum was unreachable) means the
// answer would be wrong rather than empty. Say so and return nothing.
if ($where === '' && $note !== null) {
    ok($base + ['blocked' => $note, 'used' => $used]);
}

// ---- Run it ----------------------------------------------------------------
$sqlWhere = $where !== '' ? "WHERE $where" : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts $sqlWhere");
$st->execute($args);
$total = (int)$st->fetchColumn();

$pages  = max(1, (int)ceil($total / BS_ADMIN_PER_PAGE));
$page   = min($page, $pages);
$offset = ($page - 1) * BS_ADMIN_PER_PAGE;
$per    = BS_ADMIN_PER_PAGE;

$st = $pdo->prepare(
    "SELECT id, username, email, admin_rank, status, created_at, last_login,
            forum_member_id, discord, discord_username, totp_enabled
       FROM ucp_accounts
       $sqlWhere
       ORDER BY username_lower ASC
       LIMIT $per OFFSET $offset"
);
$st->execute($args);

/* Rows come back stripped, not removed — see admin_result_out(). The page
   draws a lock on those; the count below includes them so nobody is left
   wondering why the total doesn't match what they can see. */
$actorId = (int)$acc['id'];
$rows = array_map(function ($r) use ($canSeeStaff, $actorId) {
    return admin_result_out($r, $canSeeStaff, $actorId);
}, $st->fetchAll());

$locked = 0;
foreach ($rows as $r) if (empty($r['viewable'])) $locked++;

ok([
    'tabs'     => $tabs,
    'tab'      => $key,
    'searched' => true,
    'used'     => $used,
    'results'  => $rows,
    'total'    => $total,
    'page'     => $page,
    'pages'    => $pages,
    'from'     => $total ? $offset + 1 : 0,
    'to'       => $total ? min($total, $offset + $per) : 0,
    'per_page' => $per,
    'note'     => $note,

    // How many of the rows above are staff the caller can't open. Drives one
    // explanation of the locks rather than a tooltip nobody hovers.
    'locked_staff' => $locked,

    // Characters have no table yet, so the second results panel says so
    // rather than sitting empty and reading as "this player has none".
    'characters' => ['available' => false, 'results' => []],
]);
