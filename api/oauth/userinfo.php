<?php
/**
 * BlaineSide UCP — OpenID Connect UserInfo endpoint
 * GET /api/oauth/userinfo.php
 *
 * Called by IPS with Bearer token after successful token exchange.
 * Standalone — does NOT include _bootstrap.php.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ── Debug log ────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/userinfo_debug.log';
function dbg(string $msg): void {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}
dbg('=== NEW REQUEST ===');
dbg('Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
dbg('HTTP_AUTHORIZATION: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET'));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Load config ──────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    dbg('ERROR: config.php missing');
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}
$CONFIG = require $configPath;

// ── PDO helper ───────────────────────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    global $CONFIG;
    $c = $CONFIG['db'];
    $pdo = new PDO(
        "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}",
        $c['user'], $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

require_once __DIR__ . '/_client.php';

// ── Extract Bearer token ─────────────────────────────────────────────────────
$auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (stripos($auth, 'Bearer ') === 0) {
    $token = trim(substr($auth, 7));
}
dbg('Token extracted: "' . substr($token, 0, 10) . '..." (len=' . strlen($token) . ')');

if ($token === '') {
    dbg('FAIL: no Bearer token');
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ucp.blaineside.com"');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'Missing Bearer token']);
    exit;
}

// ── Validate token ───────────────────────────────────────────────────────────
try {
    $row = oauth_validate_token($token);
    dbg('Token validation result: ' . ($row === false ? 'false (invalid/expired)' : json_encode($row)));
} catch (Throwable $e) {
    dbg('FAIL: oauth_validate_token exception — ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

if ($row === false) {
    dbg('FAIL: token invalid or expired');
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ucp.blaineside.com", error="invalid_token"');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'Token invalid or expired']);
    exit;
}

// ── Load user ────────────────────────────────────────────────────────────────
try {
    $stmt = db()->prepare(
        'SELECT id, username, email FROM ucp_accounts WHERE id = ? AND status = "active" LIMIT 1'
    );
    $stmt->execute([$row['user_id']]);
    $user = $stmt->fetch();
    dbg('User lookup result: ' . ($user ? json_encode($user) : 'NOT FOUND'));
} catch (Throwable $e) {
    dbg('FAIL: user lookup exception — ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

if (!$user) {
    dbg('FAIL: user not found or inactive');
    http_response_code(401);
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'User not found or inactive']);
    exit;
}

// ── Return OpenID Connect claims ─────────────────────────────────────────────
$claims = [
    'sub'                => (string) $user['id'],
    'name'               => $user['username'],
    'preferred_username' => $user['username'],
    'email'              => $user['email'],
    'email_verified'     => true,
];
dbg('SUCCESS: ' . json_encode($claims));
echo json_encode($claims);
