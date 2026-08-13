<?php
/**
 * GET /api/2fa-status.php
 *
 * What the security page renders on load: whether two-factor is on, how many
 * recovery codes are left, and whether this account's rank is required to
 * have it enabled.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';

$pdo = db();
$acc = twofa_current_account($pdo);

$rank    = (int)$acc['admin_rank'];
$enabled = !empty($acc['totp_enabled']) && !empty($acc['totp_secret']);

// An enabled secret that won't decrypt means the config key changed or went
// missing. Say so plainly — the alternative is the user standing there with a
// perfectly good code being told it's wrong.
$broken = $enabled && twofa_decrypt_secret($acc['totp_secret']) === null;

ok([
    'authenticated' => true,
    'name'      => $acc['username'],
    'rank'      => $rank,
    'role'      => rank_name($rank),
    'enabled'   => $enabled,
    'required'  => twofa_is_required($rank),
    'backup_remaining' => $enabled ? twofa_backup_remaining($pdo, (int)$acc['id']) : 0,
    'backup_total'     => BS_BACKUP_CODE_COUNT,
    'misconfigured'    => $broken,
]);
