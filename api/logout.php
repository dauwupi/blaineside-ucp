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
// Only POST is guarded. A forged logout is a nuisance rather than a breach, but
// the token closes it off entirely. The GET ?next= path below is a top-level
// browser navigation (used by Switch-account style links), where a token can't
// be attached — it stays as it was.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '') {
        $raw  = file_get_contents('php://input');
        $body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
        $sent = (string)($body['csrf'] ?? '');
    }
    $have = $_SESSION['csrf'] ?? '';
    if ($have === '' || $sent === '' || !hash_equals($have, $sent)) {
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.']);
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
    // Only allow http/https destinations to prevent open-redirect abuse
    $parsed = parse_url($next);
    if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
        header('Location: ' . $next);
        exit;
    }
}

// ── AJAX / default: JSON response ────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['ok' => true, 'redirect' => '/login']);
