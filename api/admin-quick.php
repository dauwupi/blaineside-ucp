<?php
/**
 * GET /api/admin-quick.php?q=…
 *
 * The top-bar quick search. One box, one guess: type a UCP name, a Discord
 * handle — later a character name or a plate — and get a short list to click
 * straight into.
 *
 * Deliberately NOT the same thing as Administrative Search. That page is for
 * "find me everyone matching these six conditions"; this is for "I have a
 * name in front of me and I want the account NOW". So it takes no filters,
 * returns at most a handful, and is the only search wired to a keystroke.
 *
 * Trainee Admin and above — the same gate as the rest of the admin tools,
 * checked here rather than only by whoever draws the box.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_ips.php';
require_once __DIR__ . '/_admin.php';

/** How many suggestions. A dropdown you have to scroll isn't a quick search. */
const BS_QUICK_LIMIT = 7;

// Higher than the other endpoints: this one fires as somebody types.
throttle('admin-quick', 90);

$pdo = db();
$acc = current_account($pdo);
require_admin_searcher($acc);

$q = trim((string)($_GET['q'] ?? ''));

if (mb_strlen($q) < 2) {
    ok(['q' => $q, 'results' => [], 'more' => 0, 'short' => true]);
}

$actorRank = (int)$acc['admin_rank'];
$actorId   = (int)$acc['id'];
$like      = admin_like($q);

/* Name first, then Discord.
 *
 * Ordered so an exact name beats a partial one: typing a full name and
 * finding it third in the list is the difference between this being faster
 * than the full search and slower. */
$sql = "SELECT id, username, email, admin_rank, status, created_at, last_login,
               forum_member_id, discord, discord_username, totp_enabled
          FROM ucp_accounts
         WHERE username_lower LIKE ? ESCAPE '|'
            OR LOWER(discord_username) LIKE ? ESCAPE '|'
            OR LOWER(discord) LIKE ? ESCAPE '|'
         ORDER BY CASE WHEN username_lower = ? THEN 0
                       WHEN username_lower LIKE ? ESCAPE '|' THEN 1
                       ELSE 2 END,
                  username_lower ASC";

$args = [$like, $like, $like, mb_strtolower($q), admin_like_prefix($q)];

$st = $pdo->prepare($sql . ' LIMIT ' . (BS_QUICK_LIMIT + 1));
$st->execute($args);
$rows = $st->fetchAll();

$more = 0;
if (count($rows) > BS_QUICK_LIMIT) {
    $rows = array_slice($rows, 0, BS_QUICK_LIMIT);
    $st = $pdo->prepare("SELECT COUNT(*) FROM ucp_accounts
                          WHERE username_lower LIKE ? ESCAPE '|'
                             OR LOWER(discord_username) LIKE ? ESCAPE '|'
                             OR LOWER(discord) LIKE ? ESCAPE '|'");
    $st->execute([$like, $like, $like]);
    $more = max(0, (int)$st->fetchColumn() - BS_QUICK_LIMIT);
}

/**
 * Suggestions are the same two shapes as the results table: a full one, or a
 * staff account reduced to the fact that it exists. Same rule, same place it
 * is enforced — admin_result_out() — so the quick search can't become the
 * hole in it.
 */
$out = [];
foreach ($rows as $r) {
    $row = admin_result_out($r, $actorRank, $actorId);
    $out[] = [
        'kind'     => 'account',
        'id'       => $row['id'],
        'name'     => $row['name'],
        'viewable' => $row['viewable'],
        'sub'      => $row['viewable']
                      ? $row['role'] . ($row['discord'] ? ' · ' . $row['discord'] : '')
                      : 'Staff account',
        'rank'     => $row['viewable'] ? $row['rank'] : null,
    ];
}

ok([
    'q'       => $q,
    'results' => $out,
    'more'    => $more,

    // What this box will also search once there is something to search. Said
    // out loud so nobody types a plate, gets nothing, and concludes the
    // vehicle isn't registered.
    'pending' => ['Characters', 'Vehicles', 'Properties'],
]);
