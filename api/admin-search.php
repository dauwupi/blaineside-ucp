<?php
/**
 * GET /api/admin-search.php
 *
 * Administrative Search. Finds UCP accounts by whatever the admin has to
 * hand — a UCP name, a Discord handle, a forum name.
 *
 *   ?field=ucp|character|forum|discord   which way to search
 *   ?q=                                  what to look for
 *   ?page=                               1-based
 *
 * With no `q` it returns the field registry and nothing else, which is what
 * the page loads with: the picker is built from the server's list, so a
 * search that has no backend yet cannot be offered as though it does.
 *
 * Trainee Admin and above. The rank is checked here, not just in the menu.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_ips.php';
require_once __DIR__ . '/_admin.php';

throttle('admin-search', 40);

$pdo = db();
$acc = current_account($pdo);
require_admin_searcher($acc);

$fields = admin_search_fields();
$key    = trim((string)($_GET['field'] ?? 'ucp'));
$q      = trim((string)($_GET['q'] ?? ''));
$page   = max(1, (int)($_GET['page'] ?? 1));

$field = admin_search_field($key);
if ($field === null) {
    json_out(['ok' => false, 'error' => 'That isn\'t something you can search by.'], 400);
}

// ---- No query: just hand back the registry ---------------------------------
if ($q === '') {
    ok([
        'fields'   => $fields,
        'field'    => $key,
        'searched' => false,
        'results'  => [],
        'total'    => 0,
    ]);
}

// ---- A field with nothing behind it refuses, and says why -------------------
if (!$field['available']) {
    ok([
        'fields'   => $fields,
        'field'    => $key,
        'searched' => false,
        'results'  => [],
        'total'    => 0,
        'blocked'  => $field['why'],
    ]);
}

if (mb_strlen($q) < BS_ADMIN_MIN_QUERY) {
    ok([
        'fields'   => $fields,
        'field'    => $key,
        'searched' => false,
        'results'  => [],
        'total'    => 0,
        'blocked'  => 'Type at least ' . BS_ADMIN_MIN_QUERY . ' characters.',
    ]);
}

list($rows, $total, $note) = admin_search_run($pdo, $key, $q, $page);

$pages = max(1, (int)ceil($total / BS_ADMIN_PER_PAGE));
$page  = min($page, $pages);

ok([
    'fields'   => $fields,
    'field'    => $key,
    'q'        => $q,
    'searched' => true,
    'results'  => $rows,
    'total'    => $total,
    'page'     => $page,
    'pages'    => $pages,
    'from'     => $total ? (($page - 1) * BS_ADMIN_PER_PAGE) + 1 : 0,
    'to'       => $total ? min($total, $page * BS_ADMIN_PER_PAGE) : 0,
    'per_page' => BS_ADMIN_PER_PAGE,
    'note'     => $note,
]);
