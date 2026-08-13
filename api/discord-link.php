<?php
/**
 * GET /api/discord-link.php
 *
 * Starts the Discord link. Sends the browser to Discord's consent screen and
 * nowhere else — the account is only written to once Discord hands us a code
 * and we exchange it ourselves, in discord-callback.php.
 *
 * This is a plain browser navigation rather than a fetch, because the whole
 * point is to leave the site. That means no CSRF token can travel with it, so
 * the protection is `state`: a random value kept in the session and required
 * to match on the way back. Without it, someone could feed a player a
 * pre-baked callback URL and attach THEIR Discord to the player's UCP.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_discord.php';

$pdo = db();

// Must be signed in. A redirect (not JSON) — a browser landed here, not fetch.
if (empty($_SESSION['uid'])) {
    $base = rtrim((string)($CONFIG['site']['base_url'] ?? ''), '/');
    header('Location: ' . $base . '/login?return=' . urlencode('/profile'));
    exit;
}

$acc = current_account($pdo);
$app = discord_app();

if ($app === null) {
    discord_return('unavailable');
}

// Already linked — nothing to do, and re-running the flow would only invite
// confusion about which account is attached.
if (!empty($acc['discord_id'])) {
    discord_return('already');
}

$state = bin2hex(random_bytes(16));
$_SESSION['discord_state']     = $state;
$_SESSION['discord_state_exp'] = time() + BS_DISCORD_STATE_TTL;

$url = BS_DISCORD_AUTH . '?' . http_build_query([
    'client_id'     => $app['id'],
    'redirect_uri'  => $app['redirect'],
    'response_type' => 'code',
    // identify only: the account id and username. Nothing else is asked for,
    // so nothing else can be granted.
    'scope'         => 'identify',
    'state'         => $state,
    // Always show the consent screen. Silent re-auth would make "link my
    // Discord" a single unexplained flash for anyone already signed in there.
    'prompt'        => 'consent',
]);

header('Location: ' . $url);
exit;
