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

// ---- Account age -----------------------------------------------------------
$createdTs  = $acc['created_at'] ? strtotime((string)$acc['created_at']) : null;
$memberDays = $createdTs ? (int)floor((time() - $createdTs) / 86400) : null;

ok([
    'authenticated' => true,
    'id'       => $uid,
    'name'     => $acc['username'],
    'email'    => mask_email((string)$acc['email']),
    'discord'  => $acc['discord'] ?: null,
    'rank'     => $rank,
    'role'     => rank_name($rank),
    'created_at'  => $acc['created_at'],
    'member_days' => $memberDays,
    'last_login'  => $acc['last_login'],
    'password_changed_at' => $acc['password_changed_at'] !== null ? (int)$acc['password_changed_at'] : null,

    'forum' => [
        'linked'    => $acc['forum_member_id'] !== null,
        'member_id' => $acc['forum_member_id'] !== null ? (int)$acc['forum_member_id'] : null,
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

    // Which sections of the page have a backend behind them. Everything false
    // here renders as an honest "not available yet" rather than sample data.
    'features' => [
        'characters'     => false,   // no character tables yet
        'record'         => false,   // administrative record system not built
        'sessions'       => false,   // needs one row per session, not one token per account
        'activity_log'   => false,   // needs a security-events table
        'discord_link'   => false,   // no OAuth app for Discord yet
        'self_delete'    => (bool)($CONFIG['security']['allow_self_delete'] ?? false),
    ],
]);
