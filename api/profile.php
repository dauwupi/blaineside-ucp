<?php
/**
 * GET /api/profile.php
 *
 * Everything the profile page renders in one call: identity, the current
 * values shown on the Settings tab, two-factor state, and a `features` block
 * telling the page which sections have a backend yet.
 *
 * That last part matters. Characters and the administrative record are
 * designed but not built, and a page that renders convincing placeholder
 * punishments to a real player is worse than one that says "not available
 * yet" — so the server decides, and the page can never drift from it.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_sessions.php';
require_once __DIR__ . '/_discord.php';
require_once __DIR__ . '/_ips.php';
require_once __DIR__ . '/_punish.php';

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

$rank    = (int)$acc['admin_rank'];
$enabled = !empty($acc['totp_enabled']) && !empty($acc['totp_secret']);

// ---- UCP name cooldown -----------------------------------------------------
$nameChangedAt = $acc['name_changed_at'] !== null ? (int)$acc['name_changed_at'] : null;
$nextChangeAt  = $nameChangedAt !== null ? $nameChangedAt + BS_NAME_CHANGE_DAYS * 86400 : null;
$nameAllowed   = $nextChangeAt === null || $nextChangeAt <= time();

// ---- Outstanding email change ----------------------------------------------
$pendingEmail = null;
if (!empty($acc['pending_email']) && (int)($acc['pending_email_expires'] ?? 0) > time()) {
    $pendingEmail = [
        'masked'  => mask_email((string)$acc['pending_email']),
        'expires' => (int)$acc['pending_email_expires'],
    ];
}

/**
 * The forum display name, for the "Linked accounts" row.
 *
 * A member number means nothing to the person reading it, so ask the forum
 * what they are actually called there. The answer is cached in the session
 * for ten minutes: this runs on every profile load, and a forum that has
 * gone slow must not drag the UCP down with it.
 *
 * Falls back to the UCP name, which is what the two are kept in step as —
 * register.php provisions the forum account with it and settings-name.php
 * pushes every rename across. Never shows the bare member id.
 */
function forum_display_name(array $acc, array $CONFIG): array
{
    $mid = (int)$acc['forum_member_id'];
    $out = ['name' => (string)$acc['username'], 'profile_url' => null];

    $cache = $_SESSION['forum_name'] ?? null;
    if (is_array($cache) && (int)($cache['id'] ?? 0) === $mid && (int)($cache['at'] ?? 0) > time() - 600) {
        return ['name' => $cache['name'], 'profile_url' => $cache['url']];
    }

    $url = ips_endpoint('core/members/' . $mid);
    if ($url === null || !function_exists('curl_init')) {
        return $out;                    // no forum API configured — UCP name it is
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => ips_userpwd(),
        CURLOPT_TIMEOUT        => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
        // The key is in the query string, so following a redirect would hand
        // it to whatever host the redirect names.
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300 && is_string($body)) {
        $j = json_decode($body, true);
        if (is_array($j)) {
            if (!empty($j['name']))       $out['name']        = (string)$j['name'];
            if (!empty($j['profileUrl'])) $out['profile_url'] = (string)$j['profileUrl'];
        }
    } else {
        error_log('UCP profile: forum lookup failed for member #' . $mid . ' (HTTP ' . $code . ')');
    }

    $_SESSION['forum_name'] = ['id' => $mid, 'name' => $out['name'],
                               'url' => $out['profile_url'], 'at' => time()];
    return $out;
}

$forum = $acc['forum_member_id'] !== null
    ? forum_display_name($acc, $CONFIG)
    : ['name' => null, 'profile_url' => null];

// ---- Device list + activity log --------------------------------------------
// Both live behind the same migration, so one check covers both. A UCP that
// hasn't run docs/migration-sessions.sql yet reports the feature as missing
// and the page keeps its honest empty state.
$tracking = sessions_available($pdo);

// ---- Account age -----------------------------------------------------------
$createdTs  = $acc['created_at'] ? strtotime((string)$acc['created_at']) : null;
$memberDays = $createdTs ? (int)floor((time() - $createdTs) / 86400) : null;

ok([
    'authenticated' => true,
    'id'       => $uid,
    'name'     => $acc['username'],
    'email'    => mask_email((string)$acc['email']),
    // Two different things, deliberately kept apart: what they typed at
    // sign-up, and what Discord itself confirmed.
    'discord'  => [
        'given'     => $acc['discord'] ?: null,
        'linked'    => !empty($acc['discord_id']),
        'username'  => $acc['discord_username'] ?: null,
        'linked_at' => $acc['discord_linked_at'] !== null ? (int)$acc['discord_linked_at'] : null,
    ],
    'rank'     => $rank,
    'role'     => rank_name($rank),
    /* Sub-group keys, for the sidebar — the Staff Report Panel opens on
       Staff Management at any administrator rank, so a menu drawn from rank
       alone is wrong for the people the sub-group exists for. Guarded: the
       table arrives with docs/migration-subgroups.sql. */
    'teams'    => (function () use ($pdo, $uid) {
        try { require_once __DIR__ . '/_teams.php'; return teams_for($pdo, $uid); }
        catch (Throwable $e) { return []; }
    })(),
    // The player's own record: everything on file, minus who issued it.
    'record'   => record_for($pdo, $uid, false),
    /* The player's own appeals. No handler names — the same rule that keeps
       the issuing administrator off their record keeps the handler off this. */
    'appeals'  => (function () use ($pdo, $uid) {
        try { require_once __DIR__ . '/_appeals.php'; return appeal_record_list($pdo, $uid, false); }
        catch (Throwable $e) { return []; }
    })(),
    'created_at'  => $acc['created_at'],
    'member_days' => $memberDays,
    'last_login'  => $acc['last_login'],
    'password_changed_at' => $acc['password_changed_at'] !== null ? (int)$acc['password_changed_at'] : null,

    'forum' => [
        'linked'    => $acc['forum_member_id'] !== null,
        'member_id' => $acc['forum_member_id'] !== null ? (int)$acc['forum_member_id'] : null,
        'name'        => $forum['name'],
        'profile_url' => $forum['profile_url'],
        'url'       => rtrim((string)($CONFIG['forum']['url'] ?? 'https://forum.blaineside.com'), '/'),
    ],

    'name_change' => [
        'allowed'      => $nameAllowed,
        'next_at'      => $nextChangeAt,
        'days_left'    => $nameAllowed || $nextChangeAt === null
                          ? 0 : (int)ceil(($nextChangeAt - time()) / 86400),
        'cooldown_days'=> BS_NAME_CHANGE_DAYS,
    ],

    'email_change' => $pendingEmail,

    'twofa' => [
        'enabled'          => $enabled,
        'required'         => twofa_is_required($rank),
        'misconfigured'    => $enabled && twofa_decrypt_secret($acc['totp_secret']) === null,
        'backup_remaining' => $enabled ? twofa_backup_remaining($pdo, $uid) : 0,
        'backup_total'     => BS_BACKUP_CODE_COUNT,
    ],

    // Where you're signed in, and what has happened to this account. Both are
    // empty arrays rather than absent when the tables aren't there — the
    // features block below is what tells the page which state to draw.
    'sessions' => $tracking ? sessions_list($pdo, $uid) : [],
    'activity' => $tracking ? security_log_list($pdo, $uid, 25) : [],

    // Which sections of the page have a backend behind them. Everything false
    // here renders as an honest "not available yet" rather than sample data.
    'features' => [
        'characters'     => false,       // no character tables yet
        /* Live once docs/migration-appeals.sql has run. Warnings and kicks
           still aren't recorded anywhere — the record says which parts of
           itself are missing rather than showing a clean sheet. */
        'record'         => punish_available($pdo),
        'sessions'       => $tracking,   // ucp_sessions
        'activity_log'   => $tracking,   // ucp_security_log
        'discord_link'   => discord_configured(),
        'self_delete'    => (bool)($CONFIG['security']['allow_self_delete'] ?? false),
    ],
]);
