<?php
/**
 * BlaineSide UCP — OAuth2 token endpoint (DEBUG VERSION)
 * Replace api/oauth/token.php temporarily, test, then restore.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ── Log helper ───────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/token_debug.log';
function dbg(string $msg): void {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}

dbg('=== NEW REQUEST ===');
dbg('Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
dbg('PHP_AUTH_USER: ' . (isset($_SERVER['PHP_AUTH_USER']) ? '"' . $_SERVER['PHP_AUTH_USER'] . '"' : 'NOT SET'));
dbg('PHP_AUTH_PW: '   . (isset($_SERVER['PHP_AUTH_PW'])   ? '(set, length=' . strlen($_SERVER['PHP_AUTH_PW']) . ')' : 'NOT SET'));
dbg('HTTP_AUTHORIZATION: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET'));
dbg('POST body: ' . json_encode($_POST));
dbg('Raw body: ' . file_get_contents('php://input'));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');

// ── Load config ───────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    dbg('ERROR: config.php missing');
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'error_description' => 'config.php missing']);
    exit;
}
$CONFIG = require $configPath;
dbg('Config loaded OK');

// ── PDO helper ────────────────────────────────────────────────────────────────
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
dbg('_client.php loaded OK');

// ── Read client credentials ───────────────────────────────────────────────────
$clientId = $clientSecret = '';

if (!empty($_SERVER['PHP_AUTH_USER'])) {
    $clientId     = $_SERVER['PHP_AUTH_USER'];
    $clientSecret = $_SERVER['PHP_AUTH_PW'] ?? '';
    dbg('Credentials from PHP_AUTH_USER: id="' . $clientId . '"');
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'Basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6), true);
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            [$clientId, $clientSecret] = explode(':', $decoded, 2);
            dbg('Credentials from HTTP_AUTHORIZATION Basic: id="' . $clientId . '"');
        }
    }
} else {
    $clientId     = $_POST['client_id']     ?? '';
    $clientSecret = $_POST['client_secret'] ?? '';
    dbg('Credentials from POST body: id="' . $clientId . '"');
}

// ── Validate grant type ───────────────────────────────────────────────────────
$grantType = $_POST['grant_type'] ?? '';
dbg('grant_type: "' . $grantType . '"');

if ($grantType !== 'authorization_code') {
    dbg('FAIL: unsupported_grant_type');
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type']);
    exit;
}

// ── Validate client ───────────────────────────────────────────────────────────
if ($clientId === '' || $clientSecret === '') {
    dbg('FAIL: missing credentials (id="' . $clientId . '" secret_len=' . strlen($clientSecret) . ')');
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Missing credentials']);
    exit;
}

try {
    $client = oauth_client($clientId);
    dbg('Client found: ' . $clientId);
} catch (Throwable $e) {
    dbg('FAIL: Unknown client — ' . $e->getMessage());
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Unknown client']);
    exit;
}

$secretMatch = hash_equals($client['client_secret'], $clientSecret);
dbg('Secret match: ' . ($secretMatch ? 'YES' : 'NO') . ' (stored_len=' . strlen($client['client_secret']) . ' given_len=' . strlen($clientSecret) . ')');

if (!$secretMatch) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Bad secret']);
    exit;
}

// ── Consume the authorization code ───────────────────────────────────────────
$code        = $_POST['code']         ?? '';
$redirectUri = $_POST['redirect_uri'] ?? '';
dbg('code: "' . substr($code, 0, 10) . '..." redirect_uri: "' . $redirectUri . '"');

try {
    $row = oauth_consume_code($code, $clientId, $redirectUri);
} catch (Throwable $e) {
    dbg('FAIL: oauth_consume_code exception — ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

if ($row === false) {
    dbg('FAIL: invalid_grant — code not found, expired, used, or redirect_uri mismatch');
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code invalid, expired, or already used']);
    exit;
}

dbg('Code consumed OK — user_id=' . $row['user_id'] . ' scope="' . $row['scope'] . '"');

// ── Issue access token ────────────────────────────────────────────────────────
try {
    $token = oauth_issue_token($clientId, (int) $row['user_id'], $row['scope']);
    dbg('Token issued OK');
} catch (Throwable $e) {
    dbg('FAIL: oauth_issue_token exception — ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

$response = [
    'access_token' => $token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600,
    'scope'        => $row['scope'],
];
dbg('SUCCESS — responding with token');
echo json_encode($response);
