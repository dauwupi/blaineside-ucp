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

/**
 * Was THIS request made over HTTPS by the browser?
 *
 * Order matters. A TLS-terminating edge (OVH, Cloudflare) forwards the
 * client's real scheme in X-Forwarded-Proto, while $_SERVER['HTTPS'] may
 * describe the edge's own connection and read "on" even when the visitor
 * is on plain HTTP. Trusting HTTPS first marks cookies Secure on an HTTP
 * page, and the browser then silently discards every one of them — no
 * session, no remember-me, straight back to the login screen.
 */
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

// ---- Sessions ----
// Cookie-based session for keeping people logged in.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => is_https(),
]);
session_name('BSUCP');
session_start();

// ---- Remember-me: restore session from DB token ----
// PHP session files get garbage-collected after session.gc_maxlifetime (default ~24 min).
// If the session expired but the user has a valid remember-me token in their cookie,
// we look it up in the DB and transparently restore their session.
if (empty($_SESSION['uid']) && !empty($_COOKIE['bsucp_rm'])) try {
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

        $secure = is_https();
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
            'secure'   => is_https(),
        ]);
    }
} catch (Throwable $e) {
    // A missing remember_token column or DB hiccup must never break page
    // loads for someone who is simply not signed in.
    error_log('UCP remember-me restore failed: ' . $e->getMessage());
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

// ============================================================
// CSRF — issued by api/csrf.php, sent back as X-CSRF-Token.
// ============================================================

/** Returns this session's CSRF token, creating one on first use. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Rejects the request unless it carries this session's CSRF token.
 * Accepts the token in the X-CSRF-Token header or a `csrf` body field.
 */
function require_csrf(): void {
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($sent === '') {
        $in   = read_input();
        $sent = (string)($in['csrf'] ?? '');
    }
    $have = $_SESSION['csrf'] ?? '';
    if ($have === '' || $sent === '' || !hash_equals($have, $sent)) {
        fail('Your session expired. Refresh the page and try again.', 419);
    }
}

// ============================================================
// Login lockout — enforced server-side, keyed by account + IP.
// The page only displays whatever locked_until the server reports;
// refreshing the browser can no longer clear it.
// ============================================================

/** Escalating lock durations, in seconds: 30s -> 5min -> 15min (then holds). */
const BS_LOCK_STEPS = [30, 300, 900];
const BS_MAX_FAILS  = 3;

function client_ip(): string {
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Returns the number of seconds remaining on an active lock, or 0 if not locked.
 * $accountId may be null for attempts against a name that doesn't exist —
 * those are tracked per-IP so name-guessing is throttled too.
 */
function lock_seconds_left(PDO $pdo, ?int $accountId, string $ip): int {
    $stmt = $pdo->prepare(
        'SELECT locked_until FROM ucp_login_attempts
          WHERE account_id <=> ? AND ip = ? LIMIT 1'
    );
    $stmt->execute([$accountId, $ip]);
    $row = $stmt->fetch();
    if (!$row) return 0;
    $left = (int)$row['locked_until'] - time();
    return $left > 0 ? $left : 0;
}

/** Records a failed attempt and locks the pair once BS_MAX_FAILS is reached. */
function record_failure(PDO $pdo, ?int $accountId, string $ip): int {
    $stmt = $pdo->prepare(
        'SELECT id, fails, lock_level FROM ucp_login_attempts
          WHERE account_id <=> ? AND ip = ? LIMIT 1'
    );
    $stmt->execute([$accountId, $ip]);
    $row = $stmt->fetch();

    $fails = ($row ? (int)$row['fails'] : 0) + 1;
    $level = $row ? (int)$row['lock_level'] : 0;
    $until = 0;

    if ($fails >= BS_MAX_FAILS) {
        $until = time() + BS_LOCK_STEPS[min($level, count(BS_LOCK_STEPS) - 1)];
        $level++;
        $fails = 0; // reset the counter; the lock itself is the penalty
    }

    if ($row) {
        $pdo->prepare(
            'UPDATE ucp_login_attempts
                SET fails = ?, lock_level = ?, locked_until = ?, updated_at = NOW()
              WHERE id = ?'
        )->execute([$fails, $level, $until, (int)$row['id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO ucp_login_attempts (account_id, ip, fails, lock_level, locked_until, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        )->execute([$accountId, $ip, $fails, $level, $until]);
    }

    return $until > 0 ? $until - time() : 0;
}

/** Clears the failure record after a successful sign-in. */
function clear_failures(PDO $pdo, ?int $accountId, string $ip): void {
    $pdo->prepare('DELETE FROM ucp_login_attempts WHERE account_id <=> ? AND ip = ?')
        ->execute([$accountId, $ip]);
}

/** How many attempts are left before the next lock kicks in. */
function attempts_left(PDO $pdo, ?int $accountId, string $ip): int {
    $stmt = $pdo->prepare(
        'SELECT fails FROM ucp_login_attempts WHERE account_id <=> ? AND ip = ? LIMIT 1'
    );
    $stmt->execute([$accountId, $ip]);
    $row = $stmt->fetch();
    $left = BS_MAX_FAILS - ($row ? (int)$row['fails'] : 0);
    return $left > 0 ? $left : 0;
}
