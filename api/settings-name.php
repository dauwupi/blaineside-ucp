<?php
/**
 * POST /api/settings-name.php
 * Body: { password, username }
 *
 * Changes the UCP name. Rate-limited to one change every 30 days, and the
 * same validation as api/check.php so the live availability check on the form
 * and the answer on submit can never disagree.
 *
 * The forum display name is pushed across afterwards, fire-and-forget — the
 * two names are meant to match, but a forum that is down must not stop the
 * UCP name changing.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('settings_name', 8);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

$in       = read_input();
$username = trim((string)($in['username'] ?? ''));

// ---- Cooldown, before the password so a locked-out change doesn't cost an
//      attempt against the lockout counter ------------------------------------
$changedAt = $acc['name_changed_at'] !== null ? (int)$acc['name_changed_at'] : null;
if ($changedAt !== null) {
    $nextAt = $changedAt + BS_NAME_CHANGE_DAYS * 86400;
    if ($nextAt > time()) {
        json_out([
            'ok'        => false,
            'field'     => 'username',
            'error'     => 'You can change your UCP name once every ' . BS_NAME_CHANGE_DAYS
                           . ' days. You can change it again in '
                           . (int)ceil(($nextAt - time()) / 86400) . ' days.',
            'next_at'   => $nextAt,
        ], 429);
    }
}

// ---- Shape ------------------------------------------------------------------
if ($username === '' || strcasecmp($username, (string)$acc['username']) === 0) {
    json_out(['ok' => false, 'field' => 'username',
              'error' => 'That is already your name.'], 400);
}
if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
    json_out(['ok' => false, 'field' => 'username',
              'error' => 'UCP names are 3–20 characters: letters, numbers and underscores.'], 400);
}
if (in_array(strtolower($username), BS_RESERVED_NAMES, true)) {
    json_out(['ok' => false, 'field' => 'username',
              'error' => 'That name is reserved and can\'t be used.'], 400);
}

// ---- It's you ---------------------------------------------------------------
require_password($pdo, $acc, (string)($in['password'] ?? ''), 'settings_name');

// ---- Still free -------------------------------------------------------------
// Re-checked here rather than trusting the live check: someone else may have
// taken it in the seconds between. The unique index on username_lower is the
// real guard; this exists to give a readable error instead of a 500.
$st = $pdo->prepare('SELECT id FROM ucp_accounts WHERE username_lower = ? AND id <> ? LIMIT 1');
$st->execute([strtolower($username), $uid]);
if ($st->fetch()) {
    json_out(['ok' => false, 'field' => 'username',
              'error' => 'That name has just been taken. Try another.'], 409);
}

$old = (string)$acc['username'];

try {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET username = ?, username_lower = ?, name_changed_at = ?
          WHERE id = ?'
    )->execute([$username, strtolower($username), time(), $uid]);
} catch (Throwable $e) {
    // Unique index rejected it — someone won the race between the check above
    // and this write.
    error_log('UCP name change failed for #' . $uid . ': ' . $e->getMessage());
    json_out(['ok' => false, 'field' => 'username',
              'error' => 'That name has just been taken. Try another.'], 409);
}

// The display name the page shows comes from the session on some views.
$_SESSION['name'] = $username;

// ---- Push the new name to the forum ----------------------------------------
// Fire-and-forget, exactly like the provisioning call in register.php: the UCP
// name has already changed and a forum outage must not roll that back. The
// hourly sync is the fallback.
if ($acc['forum_member_id'] !== null) {
    $ipsUrl = rtrim((string)($CONFIG['ips']['url'] ?? $CONFIG['ips']['api_url'] ?? ''), '/');
    $ipsKey = (string)($CONFIG['ips']['key'] ?? $CONFIG['ips']['api_key'] ?? '');
    if ($ipsUrl !== '' && $ipsKey !== '') {
        $ch = curl_init($ipsUrl . '/core/members/' . (int)$acc['forum_member_id']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['name' => $username]),
            CURLOPT_USERPWD        => $ipsKey . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            error_log('UCP name change: forum rename failed for #' . $uid . ' (HTTP ' . $code . ')');
        }
    }
}

require_once __DIR__ . '/_sessions.php';
security_log($pdo, $uid, 'name_changed',
    $old . ' → ' . $username, 'info');

ok([
    'name'     => $username,
    'previous' => $old,
    'next_at'  => time() + BS_NAME_CHANGE_DAYS * 86400,
    'message'  => 'Your UCP name is now ' . $username . '.',
]);
