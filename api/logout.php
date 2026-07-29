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
// changes. Anything that needs a link-style logout should POST a tiny form.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Logout requires POST.']);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '') {
        $raw  = file_get_contents('php://input');
        $body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
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
        )->execute([$_COOKIE['bsucp_rm']]);
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
    } elseif (isset($CONFIG['site']['base_url'])) {
        $ourHost  = parse_url($CONFIG['site']['base_url'], PHP_URL_HOST);
        $theirs   = parse_url($next, PHP_URL_HOST);
        $scheme   = strtolower((string)parse_url($next, PHP_URL_SCHEME));
        if ($ourHost && $theirs && strcasecmp($ourHost, $theirs) === 0
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
