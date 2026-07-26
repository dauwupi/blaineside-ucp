<?php
/**
 * Shared bootstrap: loads config, opens the DB, sets headers, and defines
 * small helpers used by every endpoint. Include this at the top of each API file.
 */

declare(strict_types=1);

// ---- Load config ----
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Server not configured (config.php missing).']);
    exit;
}
$CONFIG = require $configPath;

// ---- Sessions ----
// Cookie-based session for keeping people logged in.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
]);
session_name('BSUCP');
session_start();

// ---- CORS / headers ----
// Same-origin in production, but this keeps fetch() happy and locks it down.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && $origin === ($CONFIG['allowed_origin'] ?? '')) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// Handle preflight quickly.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// ---- Helpers ----
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}
function fail(string $msg, int $code = 400): void {
    json_out(['ok' => false, 'error' => $msg], $code);
}
function ok(array $extra = []): void {
    json_out(array_merge(['ok' => true], $extra));
}

/** Read a JSON body OR normal form POST into an array. */
function read_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $j = json_decode($raw, true);
        if (is_array($j)) return $j;
    }
    return $_POST ?: [];
}

/** Open a PDO connection using config. */
function db(): PDO {
    global $CONFIG, $__PDO;
    if ($__PDO instanceof PDO) return $__PDO;
    $c = $CONFIG['db'];
    $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
    try {
        $__PDO = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (Throwable $e) {
        fail('Database connection failed.', 500);
    }
    return $__PDO;
}

/** Very small rate-limit guard per session+action (defeats casual hammering). */
function throttle(string $key, int $maxPerMin = 8): void {
    $now = time();
    $bucket = $_SESSION['rl'][$key] ?? ['n' => 0, 't' => $now];
    if ($now - $bucket['t'] >= 60) { $bucket = ['n' => 0, 't' => $now]; }
    $bucket['n']++;
    $_SESSION['rl'][$key] = $bucket;
    if ($bucket['n'] > $maxPerMin) {
        fail('Too many attempts. Please wait a minute and try again.', 429);
    }
}
$__PDO = null;
