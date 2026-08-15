<?php
/**
 * GET /api/session.php
 * Returns the currently signed-in user, or { ok:false } if not logged in.
 * The dashboard calls this on load to get the real UCP name / Account ID.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_ranks.php';
require __DIR__ . '/_2fa.php';

/* A locked sign-in. Not a session — 'uid' is unset, so every authenticated
 * endpoint still refuses this browser. It is reported here, and only here, so
 * the dashboard can draw the lock notice instead of bouncing them to a sign-in
 * page that would let them straight back to the same place. */
if (empty($_SESSION['uid']) && !empty($_SESSION['locked_uid'])) {
    $pdo = db();
    $st  = $pdo->prepare('SELECT id, username, status FROM ucp_accounts WHERE id = ? LIMIT 1');
    $st->execute([(int)$_SESSION['locked_uid']]);
    $row = $st->fetch();

    // Unlocked while they sat on the page? Then the lock session is stale and
    // they should sign in properly.
    if (!$row || $row['status'] !== 'locked') {
        $_SESSION = [];
        session_destroy();
        json_out(['ok' => false, 'authenticated' => false, 'pending_2fa' => false], 200);
    }

    $lock = ['at' => null, 'by' => null, 'reason' => null];
    try {
        $st = $pdo->prepare('SELECT locked_at, locked_by_name, lock_reason FROM ucp_accounts WHERE id = ? LIMIT 1');
        $st->execute([(int)$row['id']]);
        $l = $st->fetch();
        if ($l) $lock = ['at' => $l['locked_at'] !== null ? (int)$l['locked_at'] : null,
                         'by' => $l['locked_by_name'], 'reason' => $l['lock_reason']];
    } catch (Throwable $e) {
        // Columns not migrated — the notice loses its detail, not its point.
    }

    json_out([
        'ok'            => true,
        'authenticated' => false,
        'pending_2fa'   => false,
        'locked'        => true,
        'name'          => $row['username'],
        'lock'          => $lock,
    ], 200);
}

if (empty($_SESSION['uid'])) {
    // A half-finished two-factor sign-in is NOT a session — 'uid' is unset, so
    // everything downstream correctly treats this browser as signed out. It is
    // reported separately only so the login page can tell "you never signed
    // in" from "you're one code away" and reopen the prompt.
    json_out([
        'ok'            => false,
        'authenticated' => false,
        'pending_2fa'   => !empty($_SESSION['pending_2fa'])
                           && time() <= (int)($_SESSION['pending_2fa_exp'] ?? 0),
    ], 200);
}

// Re-read from DB so rank/status changes take effect without re-login.
$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, admin_rank, status, session_epoch, totp_enabled
       FROM ucp_accounts WHERE id = ? LIMIT 1'
);
$stmt->execute([$_SESSION['uid']]);
$acc = $stmt->fetch();

if (!$acc || $acc['status'] !== 'active') {
    // account gone or no longer active — drop the session
    $_SESSION = [];
    session_destroy();
    json_out(['ok' => false, 'authenticated' => false], 200);
}

// A password reset bumps session_epoch. Any session issued before that no
// longer matches and is refused, which is how a reset ends sessions on every
// other device the account was signed in on.
if ((int)($_SESSION['epoch'] ?? 0) !== (int)$acc['session_epoch']) {
    $_SESSION = [];
    session_destroy();
    json_out(['ok' => false, 'authenticated' => false], 200);
}

$rank    = (int)$acc['admin_rank'];
$enabled = !empty($acc['totp_enabled']);

/* Sub-group keys, for the menu.
 *
 * Rank alone no longer decides the whole sidebar: the Staff Report Panel is
 * open to Management, to the Founder, and to anyone holding Staff Management
 * whatever their rank. Keys are enough for a gate — labels come from the
 * registry on the page. Guarded because the table only exists after
 * docs/migration-subgroups.sql; one migration behind, the menu falls back to
 * rank, which is right for everyone except sub-group holders and opens no
 * gate by mistake. */
$teams = [];
try {
    require_once __DIR__ . '/_teams.php';
    $teams = teams_for($pdo, (int)$acc['id']);
} catch (Throwable $e) {
}

/* Credit balance.
 *
 * Guarded, like the sub-groups above it: the table arrives with
 * docs/migration-credits.sql, and one migration behind the key is simply
 * absent. Absent is NOT zero — assets/js/ucp.js paints nothing rather than
 * a balance of 0 that nobody has any reason to believe.
 *
 * An account with no row has never held credits, which IS zero, so the
 * COALESCE is correct where the missing table is not. */
$credits = null;
try {
    $st = $pdo->prepare('SELECT balance FROM ucp_credits WHERE account_id = ? LIMIT 1');
    $st->execute([(int)$acc['id']]);
    $v = $st->fetchColumn();
    $credits = $v === false ? 0 : (int)$v;
} catch (Throwable $e) {
}

ok([
    'authenticated' => true,
    'id'     => (int)$acc['id'],
    'name'   => $acc['username'],
    'rank'   => $rank,
    'role'   => rank_name($rank),
    'teams'  => $teams,
    'remember' => !empty($_SESSION['remember']),
    'twofa'    => $enabled,
    // Set only when security.totp_required_rank is configured and this rank
    // is at or above it. The dashboard uses it to send staff to /security.
    'twofa_setup_required' => twofa_is_required($rank) && !$enabled,
] + ($credits === null ? [] : ['credits' => $credits]));
