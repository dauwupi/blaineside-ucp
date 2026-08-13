<?php
/**
 * TEMPORARY DIAGNOSTIC — delete this file when you're done.
 *
 * Runs the three calls the UCP makes to the forum and prints exactly what
 * came back, so we can stop guessing at why a rename isn't landing:
 *
 *   1. GET  /core/members/{id}     — read your forum profile
 *   2. POST /core/members/{id}     — rename, to the name you ALREADY have,
 *                                    so it proves write access without
 *                                    changing anything
 *   3. GET  sync.url               — the "UCP Name" profile field refresh
 *
 * Signed-in only, and it only ever touches the caller's own forum member.
 * The API key is redacted from the output, but the response bodies are not,
 * so read it yourself rather than pasting it somewhere public.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_ips.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
$acc = current_account($pdo);
$mid = $acc['forum_member_id'] !== null ? (int)$acc['forum_member_id'] : null;

$ipsUrl  = rtrim((string)($CONFIG['ips']['url'] ?? $CONFIG['ips']['api_url'] ?? ''), '/');
$ipsKey  = (string)($CONFIG['ips']['key'] ?? $CONFIG['ips']['api_key'] ?? '');
$syncUrl = (string)($CONFIG['sync']['url'] ?? '');
$syncKey = (string)($CONFIG['sync']['key'] ?? '');

$redact = function (string $s) use ($ipsKey, $syncKey) {
    foreach ([$ipsKey, $syncKey] as $k) {
        // Only redact something long enough to actually BE a key. A short
        // one would match ordinary text and shred the output.
        if (strlen($k) >= 8) $s = str_replace($k, '«key»', $s);
    }
    return $s;
};

echo "BlaineSide UCP — forum API diagnostic\n";
echo str_repeat('=', 62), "\n";
echo "UCP account      : #{$acc['id']} {$acc['username']}\n";
echo "forum_member_id  : ", ($mid === null ? 'NULL — nothing to call' : $mid), "\n";
echo "ips.url          : ", ($ipsUrl ?: '(not set)'), "\n";
echo "ips.key          : ", ($ipsKey === '' ? '(not set)' : 'set, ' . strlen($ipsKey) . ' chars'), "\n";
echo "sync.url         : ", ($syncUrl ?: '(not set)'), "\n";
echo "curl available   : ", function_exists('curl_init') ? 'yes' : 'NO', "\n";
echo str_repeat('=', 62), "\n\n";

if ($mid === null || $ipsUrl === '' || $ipsKey === '' || !function_exists('curl_init')) {
    echo "Nothing to test — see above.\n";
    exit;
}

/** Runs one call and prints the whole story. */
function probe(string $label, string $url, ?array $post, array $headers, ?string $userpwd, callable $redact): void
{
    echo "---- $label\n";
    echo "URL   : ", $redact($url), "\n";
    if ($post !== null) echo "BODY  : ", $redact(http_build_query($post)), "\n";

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADER         => false,
    ];
    if ($headers)  $opts[CURLOPT_HTTPHEADER] = $headers;
    if ($userpwd)  $opts[CURLOPT_USERPWD]    = $userpwd;
    if ($post !== null) {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opts);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $eff  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err  = curl_error($ch);
    curl_close($ch);

    echo "HTTP  : $code", ($err ? "   curl error: $err" : ''), "\n";
    if ($eff && $eff !== $url) echo "FINAL : ", $redact($eff), "\n";
    echo "REPLY : ", $redact(substr((string)$body, 0, 600)), "\n\n";
}

// 1. Read — key in the query string (what _ips.php now builds).
probe('1. GET /core/members/{id}',
    ips_endpoint('core/members/' . $mid),
    null, ['Accept: application/json'], ips_userpwd(), $redact);

// 2. Write — same name in, so nothing actually changes.
probe('2. POST /core/members/{id}  (name unchanged — write test only)',
    ips_endpoint('core/members/' . $mid),
    ['name' => (string)$acc['username']],
    ['Content-Type: application/x-www-form-urlencoded'], ips_userpwd(), $redact);

// 3. The profile-field sync, both ways round. The receiving script was
//    changed to read a header; if it never was, it still wants ?key=.
if ($syncUrl !== '') {
    probe('3a. GET sync.url  with X-Sync-Key header',
        $syncUrl, null, ['X-Sync-Key: ' . $syncKey], null, $redact);

    probe('3b. GET sync.url  with ?key= in the query string',
        $syncUrl . (strpos($syncUrl, '?') === false ? '?' : '&') . http_build_query(['key' => $syncKey]),
        null, [], null, $redact);
}

echo str_repeat('=', 62), "\n";
echo "Done. DELETE THIS FILE when you've read it.\n";
