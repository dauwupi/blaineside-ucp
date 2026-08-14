<?php
/**
 * GET /api/admin-account.php?id=123
 *
 * One account, as an administrator is allowed to see it: who they are, what
 * group they're in, what's linked to them, and — once the system exists —
 * their administrative record.
 *
 * What is NOT here is the point of the file. There is no settings block, no
 * security block, no session list, no email address, no tokens, no
 * password-change state. Not hidden by the page — absent from the response.
 * A read-only view that fetches everything and trusts the front end to leave
 * it out is one bug away from being an account-takeover tool.
 *
 * Trainee Admin and above, checked here rather than only in the menu.
 * Every view is written to the looking admin's security log.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require_once __DIR__ . '/_account.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_ips.php';
require_once __DIR__ . '/_teams.php';
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/_lock.php';

throttle('admin-account', 40);

$pdo = db();
$acc = current_account($pdo);
require_admin_searcher($acc);

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    json_out(['ok' => false, 'error' => 'No account asked for.'], 400);
}

/* The lock columns only exist after docs/migration-userlock.sql. Asked for
   separately so a server one migration behind still serves the page. */
$lockCols = lock_available($pdo)
    ? ', locked_at, locked_by_name, lock_reason'
    : '';

$st = $pdo->prepare(
    'SELECT id, username, admin_rank, status, created_at, last_login,
            forum_member_id, discord, discord_username, discord_linked_at,
            totp_enabled, totp_secret' . $lockCols . '
       FROM ucp_accounts WHERE id = ? LIMIT 1'
);
$st->execute([$id]);
$t = $st->fetch();

if (!$t) {
    json_out(['ok' => false, 'error' => 'There is no UCP account with that number.'], 404);
}

/* Below Management, staff accounts are off limits — see BS_ADMIN_STAFF_RANK.
 *
 * Checked here rather than only on the page. The page can be edited by anyone
 * who can open the developer tools; this cannot. Note the refusal comes AFTER
 * the row is loaded but BEFORE anything about it is returned: the answer is
 * the same whether or not the account exists, so this can't be used to test
 * which ids belong to staff. */
if (!admin_may_view(admin_can_see_staff($pdo, $acc), (int)$t['admin_rank'],
                    (int)$acc['id'] === (int)$t['id'])) {
    json_out([
        'ok'    => false,
        'code'  => 'staff_only',
        'error' => admin_view_block_reason(),
    ], 403);
}

/**
 * The forum display name for somebody else's member id.
 *
 * Cached per member for ten minutes in the session. An admin working through
 * a list of accounts would otherwise make one forum call per click, and a
 * slow forum would make the whole tool feel broken.
 */
function admin_forum_name(int $mid, array $CONFIG): array
{
    $base = rtrim((string)($CONFIG['forum']['url'] ?? 'https://forum.blaineside.com'), '/');
    $out  = ['name' => null, 'profile_url' => null];

    $cache = $_SESSION['admin_forum_names'][$mid] ?? null;
    if (is_array($cache) && (int)($cache['at'] ?? 0) > time() - 600) {
        return ['name' => $cache['name'], 'profile_url' => $cache['url']];
    }

    $url = function_exists('ips_endpoint') ? ips_endpoint('core/members/' . $mid) : null;
    if ($url !== null && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERPWD        => ips_userpwd(),
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && is_string($body)) {
            $d = json_decode($body, true);
            if (is_array($d) && !empty($d['name'])) {
                $out['name'] = (string)$d['name'];
                // Friendly URLs are off on this forum, so build the form that
                // works either way rather than trusting profileUrl.
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $out['name']));
                $out['profile_url'] = $base . '/index.php?/profile/' . $mid . '-' . trim($slug, '-') . '/';
            }
        }
    }

    $_SESSION['admin_forum_names'][$mid] = [
        'name' => $out['name'], 'url' => $out['profile_url'], 'at' => time(),
    ];
    return $out;
}

$rank      = (int)$t['admin_rank'];
$createdTs = $t['created_at'] ? strtotime((string)$t['created_at']) : null;
$forum     = $t['forum_member_id'] !== null
           ? admin_forum_name((int)$t['forum_member_id'], $CONFIG)
           : ['name' => null, 'profile_url' => null];

// Record the lookup before answering, so a response that never renders is
// still logged.
admin_log_view($pdo, $acc, $t);

ok([
    'authenticated' => true,

    'id'          => (int)$t['id'],
    'name'        => (string)$t['username'],
    'rank'        => $rank,
    'role'        => rank_name($rank),
    'status'      => (string)$t['status'],
    'teams'       => array_map(function ($k) { return ['key' => $k, 'label' => team_label($k)]; },
                               teams_for($pdo, (int)$t['id'])),

    // Null unless the account is locked right now.
    'lock'        => lock_state($t),
    'created_at'  => $t['created_at'],
    'member_days' => $createdTs ? (int)floor((time() - $createdTs) / 86400) : null,
    'last_login'  => $t['last_login'],

    // On/off only. Whether an account is protected is an administrative
    // fact; the secret behind it is not, and never leaves the database.
    'twofa' => ['enabled' => !empty($t['totp_enabled']) && !empty($t['totp_secret'])],

    'forum' => [
        'linked'      => $t['forum_member_id'] !== null,
        'member_id'   => $t['forum_member_id'] !== null ? (int)$t['forum_member_id'] : null,
        'name'        => $forum['name'],
        'profile_url' => $forum['profile_url'],
        'url'         => rtrim((string)($CONFIG['forum']['url'] ?? 'https://forum.blaineside.com'), '/'),
    ],

    'discord' => [
        'given'     => $t['discord'] ?: null,
        'linked'    => !empty($t['discord_username']),
        'username'  => $t['discord_username'] ?: null,
        'linked_at' => $t['discord_linked_at'] !== null ? (int)$t['discord_linked_at'] : null,
    ],

    // Nothing behind either yet. Same block, same meaning, same honesty as
    // api/profile.php — the page draws "not available yet", never sample data.
    'features' => [
        'characters' => false,
        'record'     => false,
    ],
    'characters'  => [],
    'punishments' => [],

    /* What the person looking at this page is allowed to DO to it.
     *
     * Worked out here rather than by the page comparing ranks: the page is
     * drawing buttons, and it should be drawing them from the same answer
     * api/member-lock.php will give when one is pressed. */
    'viewer' => [
        'id'        => (int)$acc['id'],
        'rank'      => (int)$acc['admin_rank'],
        'self'      => (int)$acc['id'] === (int)$t['id'],
        'may_lock'  => lock_available($pdo) && lock_block_reason($acc, $t) === null,
        'lock_why'  => lock_block_reason($acc, $t),
        'lock_min'  => rank_name(BS_LOCK_MIN_RANK),
    ],
]);
