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

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ── Load config ──────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
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

if ($token === '') {
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="ucp.blaineside.com"');
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'Missing Bearer token']);
    exit;
}

// ── Validate token ───────────────────────────────────────────────────────────
try {
    $row = oauth_validate_token($token);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

if ($row === false) {
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
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_token', 'error_description' => 'User not found or inactive']);
    exit;
}

// ── Return OpenID Connect claims ─────────────────────────────────────────────
echo json_encode([
    'sub'                => (string) $user['id'],
    'name'               => $user['username'],
    'preferred_username' => $user['username'],
    'email'              => $user['email'],
    'email_verified'     => true,
]);
