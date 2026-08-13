<?php
/**
 * BlaineSide UCP — one place that knows how to address the forum's REST API.
 *
 * Every call used to authenticate with HTTP Basic (API key as the username),
 * which is what the IPS documentation shows. On this install the forum answers
 * every one of them with:
 *
 *     401  {"errorCode":"2S290\/6","errorMessage":"NO_API_KEY"}
 *
 * — the key never reaches IPS at all. Apache hands the Authorization header to
 * PHP only in some configurations; under CGI/FastCGI it is commonly stripped
 * before any script sees it, and no amount of correct key gets past that.
 *
 * So the key travels in the query string instead, which is IPS's other
 * documented form and the one that works regardless of how PHP is being run.
 * Basic auth is still sent alongside: where the header does survive, IPS reads
 * that and ignores the parameter, and we lose nothing.
 *
 * The other half of the problem is the '?' in the configured URL. This forum
 * has friendly URLs off, so the base is:
 *
 *     https://forum.blaineside.com/api/index.php?
 *
 * and the path is appended to make .../index.php?/core/members/1 — which means
 * the URL already contains a '?', and anything else has to join with '&'.
 * Getting that wrong produces a URL the forum answers with a generic error,
 * which is impossible to tell apart from a permissions problem. One helper,
 * used everywhere, so the question is answered once.
 *
 * Include AFTER _bootstrap.php.
 */

declare(strict_types=1);

/** The API key, whichever spelling config.php uses. Empty string if unset. */
function ips_key(): string
{
    global $CONFIG;
    return (string)($CONFIG['ips']['key'] ?? $CONFIG['ips']['api_key'] ?? '');
}

/**
 * Builds a full API URL with the key attached.
 *
 * @param string $path  Endpoint path, e.g. 'core/members/12'
 * @param array  $query Extra parameters
 * @return string|null  null when the forum API isn't configured at all
 */
function ips_endpoint(string $path, array $query = []): ?string
{
    global $CONFIG;

    $base = (string)($CONFIG['ips']['url'] ?? $CONFIG['ips']['api_url'] ?? '');
    $key  = ips_key();
    if ($base === '' || $key === '') return null;

    // rtrim only the slash: a trailing '?' is meaningful here.
    $base = rtrim($base, '/');
    $url  = $base . '/' . ltrim($path, '/');

    $query['key'] = $key;

    // '.../index.php?/core/members/1' already has its '?', so the parameters
    // have to join with '&'. A friendly-URL install has neither and starts
    // its query string here.
    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);

    return $url;
}

/** Basic-auth credentials for CURLOPT_USERPWD — belt and braces, see above. */
function ips_userpwd(): string
{
    return ips_key() . ':';
}
