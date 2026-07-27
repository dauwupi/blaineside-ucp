<?php
/**
 * BlaineSide UCP — OAuth2 token endpoint (server-to-server, no session needed)
 * POST /api/oauth/token.php
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');

// ── Load config ──────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'error_description' => 'config.php missing']);
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

// ── Parse request ────────────────────────────────────────────────────────────
$grantType    = $_POST['grant_type']    ?? '';
$code         = $_POST['code']          ?? '';
$redirectUri  = $_POST['redirect_uri']  ?? '';
$codeVerifier = $_POST['code_verifier'] ?? '';

if ($grantType !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type']);
    exit;
}

// ── Read client credentials ──────────────────────────────────────────────────
$clientId = $clientSecret = '';

if (!empty($_SERVER['PHP_AUTH_USER'])) {
    $clientId     = $_SERVER['PHP_AUTH_USER'];
    $clientSecret = $_SERVER['PHP_AUTH_PW'] ?? '';
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'Basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6), true);
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            [$clientId, $clientSecret] = explode(':', $decoded, 2);
        }
    }
} else {
    $clientId     = $_POST['client_id']     ?? '';
    $clientSecret = $_POST['client_secret'] ?? '';
}

// ── PKCE public-client path ──────────────────────────────────────────────────
// IPS sends a code_verifier even alongside Basic auth credentials.
// We treat it as PKCE-only when no client_id was supplied at all.
$isPkce = ($clientId === '' && $codeVerifier !== '');

if ($isPkce) {
    if ($code === '') {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'code required']);
        exit;
    }

    // Peek at the code to find the client_id
    try {
        $peek = db()->prepare('SELECT client_id FROM ucp_oauth_codes WHERE code = ? LIMIT 1');
        $peek->execute([$code]);
        $peekRow = $peek->fetch();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if (!$peekRow) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code not found']);
        exit;
    }

    $clientId = $peekRow['client_id'];

    try {
        $client = oauth_client($clientId);
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Unknown client']);
        exit;
    }

    try {
        $row = oauth_consume_code($code, $clientId, $redirectUri, $codeVerifier);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if ($row === false) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code invalid, expired, or PKCE mismatch']);
        exit;
    }

} else {
    if ($clientId === '' || $clientSecret === '') {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Missing credentials']);
        exit;
    }

    try {
        $client = oauth_client($clientId);
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Unknown client']);
        exit;
    }

    if (!hash_equals($client['client_secret'], $clientSecret)) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Bad secret']);
        exit;
    }

    try {
        $row = oauth_consume_code($code, $clientId, $redirectUri, $codeVerifier);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if ($row === false) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code invalid, expired, or already used']);
        exit;
    }
}

// ── Issue access token ───────────────────────────────────────────────────────
try {
    $token = oauth_issue_token($clientId, (int) $row['user_id'], $row['scope']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

echo json_encode([
    'access_token' => $token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600,
    'scope'        => $row['scope'],
]);
