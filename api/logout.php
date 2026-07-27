<?php
/**
 * BlaineSide UCP — Logout endpoint
 * GET/POST /api/logout.php
 *
 * Destroys the UCP session and either:
 *   - Redirects to ?next= (if a valid http/https URL is supplied), or
 *   - Returns JSON {"ok":true,"redirect":"login.html"} for AJAX callers
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    $CONFIG = require $configPath;
}

$secure = (($_SERVER['HTTPS'] ?? '') === 'on');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $secure,
]);
session_name('BSUCP');
session_start();

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
echo json_encode(['ok' => true, 'redirect' => 'login.html']);
