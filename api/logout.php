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

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
]);
session_name('BSUCP');
session_start();
session_unset();
session_destroy();

// Clear the session cookie
$cookieParams = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $cookieParams['path'],
    'httponly' => $cookieParams['httponly'],
    'samesite' => 'Lax',
    'secure'   => $cookieParams['secure'],
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
