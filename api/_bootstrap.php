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

// ---- Remember-me: restore session from DB token ----
// PHP session files get garbage-collected after session.gc_maxlifetime (default ~24 min).
// If the session expired but the user has a valid remember-me token in their cookie,
// we look it up in the DB and transparently restore their session.
if (empty($_SESSION['uid']) && !empty($_COOKIE['bsucp_rm'])) {
    $rm_cookie = $_COOKIE['bsucp_rm'];
    $rm_pdo    = db();
    $rm_stmt   = $rm_pdo->prepare(
        'SELECT id, username, admin_rank
           FROM ucp_accounts
          WHERE remember_token = ?
            AND remember_expires > ?
            AND status = \'active\'
          LIMIT 1'
    );
    $rm_stmt->execute([$rm_cookie, time()]);
    $rm_acc = $rm_stmt->fetch();

    if ($rm_acc) {
        // Valid token — restore the session.
        session_regenerate_id(true);
        $_SESSION['uid']      = (int)$rm_acc['id'];
        $_SESSION['name']     = $rm_acc['username'];
        $_SESSION['rank']     = (int)$rm_acc['admin_rank'];
        $_SESSION['remember'] = true;

        // Rotate the token so each use issues a fresh one (prevents replay if ever leaked).
        $new_token   = bin2hex(random_bytes(32));
        $new_expires = time() + 30 * 24 * 3600;
        $rm_pdo->prepare(
            'UPDATE ucp_accounts SET remember_token = ?, remember_expires = ? WHERE id = ?'
        )->execute([$new_token, $new_expires, (int)$rm_acc['id']]);

        $secure = (($_SERVER['HTTPS'] ?? '') === 'on');
        setcookie('bsucp_rm', $new_token, [
            'expires'  => $new_expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
        // Also slide the session cookie forward so it survives the browser session.
        setcookie(session_name(), session_id(), [
            'expires'  => $new_expires,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
    } else {
        // Token not found / expired — clear the stale cookie.
        setcookie('bsucp_rm', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
    }
}

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
