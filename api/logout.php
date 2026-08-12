<?php
/**
 * BlaineSide UCP — Logout endpoint
 * GET/POST /api/logout.php
 *
 * Destroys the UCP session and either:
 *   - Redirects to ?next= (if a valid http/https URL is supplied), or
 *   - Returns JSON {"ok":true,"redirect":"/login"} for AJAX callers
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    $CONFIG = require $configPath;
}

/* This endpoint is standalone (no _bootstrap.php), so it carries its own
   copy of the scheme check. Keep it identical to the one in
   _bootstrap.php — if the two disagree, logout writes a cookie with
   different flags than login did and fails to clear the session. */
function is_https(): bool {
    // 1. A TLS-terminating edge states the CLIENT's scheme here. Most
    //    authoritative signal available, so it wins outright.
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return strtolower(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]) === 'https';
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL'])) {
        return strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on';
    }

    // 2. REQUEST_SCHEME describes the connection Apache actually accepted.
    //    If it says http, believe it even when HTTPS is set to "on" — some
    //    hosts set HTTPS globally regardless of how the visitor arrived.
    $scheme = strtolower($_SERVER['REQUEST_SCHEME'] ?? '');
    if ($scheme === 'http')  return false;
    if ($scheme === 'https') return true;

    $h = $_SERVER['HTTPS'] ?? '';
    if ($h !== '' && strtolower($h) !== 'off') return true;
    return ((int)($_SERVER['SERVER_PORT'] ?? 0)) === 443;
}

$secure = is_https();

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);
session_name('BSUCP');
session_start();

// ── CSRF ─────────────────────────────────────────────────────────────────────
// POST + CSRF token, always.
//
// This used to accept a bare GET. That meant any other site could end a
// visitor's session just by linking here (or by loading the URL), because
// SameSite=Lax still sends the cookie on a top-level GET navigation. Not a
// breach — nothing is exposed — but it reads to users as "the UCP keeps
// signing me out", and it is trivial to abuse.
//
// The dashboard already sends POST with a token, so nothing user-facing
// changes. Browser-level GET navigations (e.g. the IPS forum logout redirect)
// get an intermediate HTML page that auto-submits a POST with the real CSRF
// token — security is preserved, and the redirect still works.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    // Ensure the CSRF token exists in the session (session_start() already ran).
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    $csrf_token = $_SESSION['csrf'];
    $next_val   = htmlspecialchars($_GET['next'] ?? '', ENT_QUOTES, 'UTF-8');
    $action     = htmlspecialchars($_SERVER['PHP_SELF'] ?? '/api/logout.php', ENT_QUOTES, 'UTF-8');
    // Flush the session to disk NOW, before we send the page that will
    // auto-submit a POST back to us. Without this, the GET and POST requests
    // can race: the POST arrives while the GET's session write is still
    // pending, so $_SESSION['csrf'] looks empty on the POST side.
    session_write_close();
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Signing out… — BlaineSide UCP</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;
  background:#100f0e;font-family:Inter,system-ui,sans-serif;color:#f1efe9}
.card{text-align:center;max-width:360px;padding:36px 30px;background:#1a1816;
  border:1px solid #332e27;border-radius:14px}
h1{font-size:18px;font-weight:700;margin:0 0 8px}
p{font-size:13.5px;color:#a49a8c;margin:0 0 20px}
button{padding:11px 22px;background:#d4923a;color:#1a1206;border:none;
  border-radius:8px;font-weight:700;font-size:14px;cursor:pointer}
</style></head>
<body>
<div class="card">
  <h1>Signing out…</h1>
  <p>Please wait while we sign you out securely.</p>
  <form id="lf" method="POST" action="{$action}">
    <input type="hidden" name="next"  value="{$next_val}">
    <input type="hidden" name="csrf"  value="{$csrf_token}">
    <noscript><button type="submit">Sign out</button></noscript>
  </form>
</div>
<script>document.getElementById('lf').submit();</script>
</body></html>
HTML;
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '') {
        $raw  = file_get_contents('php://input');
        // json_decode returns null on failure; ?? falls back to $_POST so
        // URL-encoded form submissions (from the GET→POST bridge page) are
        // handled correctly alongside JSON bodies (from the dashboard fetch).
        $body = ($raw !== '') ? (json_decode($raw, true) ?? $_POST) : $_POST;
        $sent = (string)($body['csrf'] ?? '');
    }
    $have = $_SESSION['csrf'] ?? '';
    if ($have === '' || $sent === '' || !hash_equals($have, $sent)) {
        // See _bootstrap.php: 419 is rewritten to 500 by Apache.
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'csrf' => true, 'error' => 'Your session expired. Refresh the page and try again.']);
        exit;
    }
}

// ── Clear remember-me token from the database ─────────────────────────────────
// Must happen before session_destroy() since we may need the uid.
if (!empty($_COOKIE['bsucp_rm']) && isset($CONFIG)) {
    $c   = $CONFIG['db'];
    $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->prepare(
            'UPDATE ucp_accounts SET remember_token = NULL, remember_expires = NULL
              WHERE remember_token = ?'
        )->execute([hash('sha256', $_COOKIE['bsucp_rm'])]);
    } catch (Throwable $e) { /* DB down — still proceed with cookie/session cleanup */ }
}

// ── Destroy the session ───────────────────────────────────────────────────────
session_unset();
session_destroy();

// Clear the session cookie.
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);

// Clear the remember-me cookie.
setcookie('bsucp_rm', '', [
    'expires'  => time() - 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);

// Device-tracking cookies belong to the account that just signed out. Leaving
// them behind meant the next person on a shared machine was greeted with the
// previous user's "last sign-in ... from this device" notice.
foreach (['bsucp_dev', 'bsucp_last'] as $c) {
    setcookie($c, '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => ($c === 'bsucp_dev'),
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
}

// ── ?next redirect (for browser-initiated logout, e.g. Switch button) ────────
$next = $_GET['next'] ?? $_POST['next'] ?? '';
if ($next !== '') {
    // Same-site destinations only. Checking the SCHEME is not the same as
    // checking the HOST: "https://evil.example/" passed a scheme test
    // happily, turning this endpoint into a one-click open redirect from a
    // trusted domain — ideal for phishing. Accept a site-relative path, or
    // an absolute URL whose host matches our own configured base_url.
    $allowed = null;

    if ($next[0] === '/' && ($next[1] ?? '') !== '/' && ($next[1] ?? '') !== '\\') {
        $allowed = $next;                       // e.g. "/login", "/dashboard"
    } else {
        // Build a list of trusted hosts: the UCP domain + the IPS forum domain.
        $trustedHosts = [];
        if (isset($CONFIG['site']['base_url'])) {
            $h = parse_url($CONFIG['site']['base_url'], PHP_URL_HOST);
            if ($h) $trustedHosts[] = strtolower($h);
        }
        if (isset($CONFIG['ips']['url'])) {
            $h = parse_url($CONFIG['ips']['url'], PHP_URL_HOST);
            if ($h) $trustedHosts[] = strtolower($h);
        }
        $theirs = strtolower((string)parse_url($next, PHP_URL_HOST));
        $scheme = strtolower((string)parse_url($next, PHP_URL_SCHEME));
        if ($theirs && in_array($theirs, $trustedHosts, true)
            && in_array($scheme, ['http', 'https'], true)) {
            $allowed = $next;
        }
    }

    if ($allowed !== null) {
        header('Location: ' . $allowed);
        exit;
    }
    // Anything else: fall through to the JSON response rather than obeying it.
}

// ── AJAX / default: JSON response ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => true, 'redirect' => '/login']);
