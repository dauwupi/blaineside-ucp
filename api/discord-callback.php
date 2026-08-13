<?php
/**
 * GET /api/discord-callback.php?code=…&state=…
 *
 * Where Discord sends the player back. Exchanges the one-time code for an
 * access token, asks Discord who it belongs to, stores the id and username,
 * and throws the token away.
 *
 * Returns a redirect rather than JSON in every case: a browser is driving,
 * and the profile page turns ?discord=… into a sentence the player can act on.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_discord.php';
require_once __DIR__ . '/_sessions.php';

$pdo = db();

if (empty($_SESSION['uid'])) {
    $base = rtrim((string)($CONFIG['site']['base_url'] ?? ''), '/');
    header('Location: ' . $base . '/login?return=' . urlencode('/profile'));
    exit;
}

$acc = current_account($pdo);
$uid = (int)$acc['id'];
$app = discord_app();

if ($app === null) discord_return('unavailable');

// ---- The state check ------------------------------------------------------
// Consumed whatever happens, so a stale tab can't be replayed later.
$state    = (string)($_GET['state'] ?? '');
$expected = (string)($_SESSION['discord_state'] ?? '');
$expiry   = (int)($_SESSION['discord_state_exp'] ?? 0);
unset($_SESSION['discord_state'], $_SESSION['discord_state_exp']);

if ($expected === '' || $state === '' || !hash_equals($expected, $state) || time() > $expiry) {
    discord_return('state');
}

// Discord says no — the player pressed Cancel, or the app was rejected.
if (!empty($_GET['error']) || empty($_GET['code'])) {
    discord_return('denied');
}

// ---- Code -> token --------------------------------------------------------
$tok = discord_call(BS_DISCORD_TOKEN, [
    'client_id'     => $app['id'],
    'client_secret' => $app['secret'],
    'grant_type'    => 'authorization_code',
    'code'          => (string)$_GET['code'],
    'redirect_uri'  => $app['redirect'],
]);

if (!$tok || empty($tok['access_token'])) {
    discord_return('failed');
}

// ---- Token -> who they are ------------------------------------------------
$user = discord_call(BS_DISCORD_API . '/users/@me', null, [
    'Authorization: Bearer ' . $tok['access_token'],
]);

// The token has done its one job. Handing it back to Discord means it stops
// working the moment this request ends, instead of sitting valid for a week
// in somebody's logs.
discord_call(BS_DISCORD_TOKEN . '/revoke', [
    'client_id'     => $app['id'],
    'client_secret' => $app['secret'],
    'token'         => $tok['access_token'],
]);

if (!$user || empty($user['id'])) {
    discord_return('failed');
}

$discordId   = (string)$user['id'];
$discordName = discord_display_name($user);

// ---- One Discord account, one UCP -----------------------------------------
// The unique index would refuse this anyway; catching it here is what turns a
// database error into a sentence the player understands.
$claimed = $pdo->prepare('SELECT id FROM ucp_accounts WHERE discord_id = ? AND id <> ? LIMIT 1');
$claimed->execute([$discordId, $uid]);
if ($claimed->fetch()) {
    security_log($pdo, $uid, 'discord_link_failed',
        'That Discord account is already on another UCP', 'warn');
    discord_return('taken');
}

try {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET discord_id = ?, discord_username = ?, discord_linked_at = ?
          WHERE id = ?'
    )->execute([$discordId, $discordName, time(), $uid]);
} catch (Throwable $e) {
    error_log('UCP discord link write failed for #' . $uid . ': ' . $e->getMessage());
    discord_return('failed');
}

security_log($pdo, $uid, 'discord_linked', $discordName, 'good');

discord_return('linked');
