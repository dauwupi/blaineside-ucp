<?php
/**
 * BlaineSide UCP — shared bits of the Discord account link.
 *
 * The link is deliberately narrow: it asks Discord for the `identify` scope
 * and nothing else. That returns the account id and current username, which
 * is all the UCP needs in order to say "this really is them". No messages, no
 * server list, no email, no ability to act on their behalf — the access token
 * is used once, immediately, and never stored.
 *
 * Include AFTER _bootstrap.php.
 */

declare(strict_types=1);

const BS_DISCORD_API   = 'https://discord.com/api/v10';
const BS_DISCORD_AUTH  = 'https://discord.com/oauth2/authorize';
const BS_DISCORD_TOKEN = 'https://discord.com/api/oauth2/token';

/** How long an in-flight link attempt stays valid. */
const BS_DISCORD_STATE_TTL = 600;

/**
 * The configured application, or null if linking isn't set up.
 *
 * Every caller checks this. A half-configured UCP shows "not available yet"
 * on the profile page rather than bouncing people to a Discord error screen.
 */
function discord_app(): ?array
{
    global $CONFIG;

    $id     = trim((string)($CONFIG['discord']['client_id'] ?? ''));
    $secret = trim((string)($CONFIG['discord']['client_secret'] ?? ''));
    if ($id === '' || $secret === '') return null;

    $redirect = trim((string)($CONFIG['discord']['redirect_uri'] ?? ''));
    if ($redirect === '') {
        $base = rtrim((string)($CONFIG['site']['base_url'] ?? 'https://ucp.blaineside.com'), '/');
        $redirect = $base . '/api/discord-callback.php';
    }

    return ['id' => $id, 'secret' => $secret, 'redirect' => $redirect];
}

/** Whether the profile page should offer the button at all. */
function discord_configured(): bool
{
    return discord_app() !== null;
}

/**
 * POST/GET to Discord and decode the reply.
 *
 * Returns null on anything that isn't a clean 2xx JSON body — the callers
 * treat that as "the link didn't happen" and say so, rather than half-writing
 * a link from a partial response.
 */
function discord_call(string $url, ?array $post = null, array $headers = []): ?array
{
    if (!function_exists('curl_init')) return null;

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
    ];
    if ($post !== null) {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
        $opts[CURLOPT_HTTPHEADER][] = 'Content-Type: application/x-www-form-urlencoded';
    }
    curl_setopt_array($ch, $opts);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code < 200 || $code >= 300 || !is_string($body)) {
        error_log('UCP discord: ' . $url . ' -> HTTP ' . $code . ' ' . $err . ' ' . substr((string)$body, 0, 200));
        return null;
    }

    $j = json_decode($body, true);
    return is_array($j) ? $j : null;
}

/**
 * The name to show for a linked account.
 *
 * Discord is mid-migration from Name#1234 to unique handles: modern accounts
 * report discriminator "0" and the username IS the handle. Old ones still
 * carry a real discriminator, and dropping it there would print a name that
 * doesn't identify anyone.
 */
function discord_display_name(array $user): string
{
    $name = (string)($user['username'] ?? '');
    $disc = (string)($user['discriminator'] ?? '0');
    if ($name === '') return 'Unknown';
    return ($disc === '' || $disc === '0') ? $name : $name . '#' . $disc;
}

/** Sends the browser back to the profile page with a one-word outcome. */
function discord_return(string $outcome): void
{
    global $CONFIG;
    $base = rtrim((string)($CONFIG['site']['base_url'] ?? ''), '/');
    header('Location: ' . $base . '/profile?discord=' . urlencode($outcome));
    exit;
}
