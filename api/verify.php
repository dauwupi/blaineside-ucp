<?php
/**
 * GET /api/verify.php?token=…
 * Clicked from the verification email. Activates the account, creates the
 * matching IPS forum member via REST API, then shows a branded confirmation page.
 */
require __DIR__ . '/_bootstrap.php';

// This endpoint returns HTML (it's opened in a browser), not JSON.
header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');

$token = (string)($_GET['token'] ?? '');
$base  = rtrim($CONFIG['site']['base_url'], '/');

function verify_page(string $title, string $body, string $base): void {
    echo <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>$title — BlaineSide UCP</title>
<style>
  body{margin:0;height:100vh;display:grid;place-items:center;background:#100f0e;
    font-family:Inter,system-ui,sans-serif;color:#f1efe9}
  .card{max-width:420px;text-align:center;background:#1a1815;border:1px solid #26221e;
    border-radius:14px;padding:34px 30px}
  h1{font-size:20px;margin:0 0 10px}
  p{font-size:14px;line-height:1.6;color:#c9bea9;margin:0 0 20px}
  a{display:inline-block;background:linear-gradient(145deg,#e2b65c,#d4923a);color:#1a1206;
    font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px}
  .wm{font-family:Oswald,sans-serif;font-weight:700;letter-spacing:2px;margin-bottom:18px;color:#f1efe9}
  .wm b{color:#e2b65c}
</style></head><body>
  <div class="card">
    <div class="wm">BLAINE<b>SIDE</b></div>
    <h1>$title</h1>
    <p>$body</p>
    <a href="$base/login.html">Go to sign in</a>
  </div>
</body></html>
HTML;
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    verify_page('Invalid link', 'This verification link is malformed or incomplete.', $base);
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, email, password_hash, status FROM ucp_accounts WHERE verify_token = ? LIMIT 1'
);
$stmt->execute([$token]);
$acc = $stmt->fetch();

if (!$acc) {
    verify_page('Link expired', 'This link is invalid or has already been used. Try signing in — your account may already be active.', $base);
}
if ($acc['status'] === 'active') {
    verify_page('Already verified', 'Your account is already active. You can sign in now.', $base);
}

// ── Activate the UCP account ──────────────────────────────────────────────────
$pdo->prepare('UPDATE ucp_accounts SET status = "active", verify_token = NULL WHERE id = ?')
    ->execute([$acc['id']]);

// ── Create the matching IPS forum member via REST API ─────────────────────────
// config.php must contain: $CONFIG['ips']['url'] and $CONFIG['ips']['key']
$ips_url = rtrim($CONFIG['ips']['url'] ?? '', '/');   // e.g. https://forum.blaineside.com
$ips_key = $CONFIG['ips']['key'] ?? '';

if ($ips_url !== '' && $ips_key !== '') {
    $forum_member_id = ips_create_or_find_member(
        $ips_url, $ips_key,
        $acc['username'], $acc['email'], $acc['password_hash']
    );

    if ($forum_member_id !== null) {
        $pdo->prepare('UPDATE ucp_accounts SET forum_member_id = ? WHERE id = ?')
            ->execute([$forum_member_id, $acc['id']]);
    }
    // If the API call fails we still show success — the account is active.
    // forum_member_id can be back-filled later if needed.
}

verify_page('Email verified ✓', 'Your BlaineSide UCP account is now active. Welcome aboard — you can sign in.', $base);

// ── IPS REST API helpers ──────────────────────────────────────────────────────

/**
 * Try to create an IPS member. If the username/email is already taken on the
 * forum, fall back to looking up the existing member ID so we can still link
 * the accounts. Returns the IPS member ID (int) or null on failure.
 */
function ips_create_or_find_member(string $base, string $key, string $name, string $email, string $password_hash): ?int
{
    // We need to send a real password to IPS. Because we only store a bcrypt
    // hash, we generate a fresh random password. The user will always log in
    // via UCP SSO (OAuth), so the IPS password is never used directly.
    $tmp_password = bin2hex(random_bytes(16)) . 'Aa1!';

    // IPS accepts the API key as a query parameter (?key=) or via Basic auth.
    // Using query param here as it's more reliable across PHP/cURL versions.
    // Base URL ends with '?' (e.g. https://forum.../api/index.php?)
    // IPS route format: index.php?/core/members&key=<key>
    $endpoint = $base . '/core/members&key=' . urlencode($key);
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'name'     => $name,
            'email'    => $email,
            'password' => $tmp_password,
        ]),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POSTREDIR      => 3,
        CURLOPT_MAXREDIRS      => 3,
    ]);
    $body  = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    // Debug log — remove once forum_member_id is populating reliably
    file_put_contents(
        __DIR__ . '/ips_debug.log',
        date('Y-m-d H:i:s') . " POST $endpoint\n"
            . "  curl_error: $curl_error\n"
            . "  http_code:  $code\n"
            . "  body:       " . substr($body ?: '', 0, 400) . "\n\n",
        FILE_APPEND
    );

    if ($body === false || $curl_error) return null;

    $data = json_decode($body, true);

    // 201 Created — member was made successfully.
    if ($code === 201 && isset($data['id'])) {
        return (int)$data['id'];
    }

    // IPS returns 400 with errorCode "1S290/2" or similar when name/email already exists.
    // Fall back to a search so we can still link the existing forum account.
    if ($code === 400 || $code === 409) {
        return ips_find_member_by_email($base, $key, $email);
    }

    return null;
}

/**
 * Look up an existing IPS member by email address.
 * Returns the member ID or null if not found / API error.
 */
function ips_find_member_by_email(string $base, string $key, string $email): ?int
{
    $url = $base . '/core/members&' . http_build_query(['key' => $key, 'email' => $email]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code !== 200) return null;

    $data = json_decode($body, true);

    // Response shape: { "results": [ { "id": 1, ... }, ... ] }
    if (isset($data['results'][0]['id'])) {
        return (int)$data['results'][0]['id'];
    }

    return null;
}
