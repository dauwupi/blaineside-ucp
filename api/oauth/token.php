<?php
/**
 * BlaineSide UCP — OAuth2 token endpoint (server-to-server, no session needed)
 * POST /api/oauth/token.php
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ── Debug log ────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/token_debug.log';
function dbg(string $msg): void {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $msg . "\n", FILE_APPEND);
}
dbg('=== NEW REQUEST ===');
dbg('PHP_AUTH_USER: ' . (isset($_SERVER['PHP_AUTH_USER']) ? '"'.$_SERVER['PHP_AUTH_USER'].'"' : 'NOT SET'));
dbg('HTTP_AUTHORIZATION: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET'));
dbg('POST: ' . json_encode($_POST));

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');

// ── Load config ──────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    dbg('ERROR: config.php missing');
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

dbg('grant_type="'.$grantType.'" code="'.substr($code,0,10).'..." redirect_uri="'.$redirectUri.'" code_verifier_len='.strlen($codeVerifier));

if ($grantType !== 'authorization_code') {
    dbg('FAIL: unsupported_grant_type');
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type']);
    exit;
}

// ── Read client credentials ──────────────────────────────────────────────────
$clientId = $clientSecret = '';

if (!empty($_SERVER['PHP_AUTH_USER'])) {
    $clientId     = $_SERVER['PHP_AUTH_USER'];
    $clientSecret = $_SERVER['PHP_AUTH_PW'] ?? '';
    dbg('Creds: Basic auth, id="'.$clientId.'"');
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'Basic ') === 0) {
        $decoded = base64_decode(substr($auth, 6), true);
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            [$clientId, $clientSecret] = explode(':', $decoded, 2);
            dbg('Creds: HTTP_AUTHORIZATION Basic, id="'.$clientId.'"');
        }
    }
} else {
    $clientId     = $_POST['client_id']     ?? '';
    $clientSecret = $_POST['client_secret'] ?? '';
    dbg('Creds: POST body, id="'.$clientId.'"');
}

// ── PKCE public-client path ──────────────────────────────────────────────────
$isPkce = ($clientId === '' && $codeVerifier !== '');
dbg('isPkce='.($isPkce?'true':'false'));

if ($isPkce) {
    if ($code === '') {
        dbg('FAIL: PKCE but no code');
        http_response_code(400);
        echo json_encode(['error' => 'invalid_request', 'error_description' => 'code required']);
        exit;
    }

    // Peek at the code to find the client_id
    try {
        $peek = db()->prepare('SELECT client_id FROM ucp_oauth_codes WHERE code = ? LIMIT 1');
        $peek->execute([$code]);
        $peekRow = $peek->fetch();
        dbg('Peek result: '.json_encode($peekRow));
    } catch (Throwable $e) {
        dbg('FAIL: peek exception — '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if (!$peekRow) {
        dbg('FAIL: code not found in DB');
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code not found']);
        exit;
    }

    $clientId = $peekRow['client_id'];
    dbg('client_id from code: "'.$clientId.'"');

    try {
        $client = oauth_client($clientId);
        dbg('Client found OK');
    } catch (Throwable $e) {
        dbg('FAIL: unknown client — '.$e->getMessage());
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Unknown client']);
        exit;
    }

    try {
        $row = oauth_consume_code($code, $clientId, $redirectUri, $codeVerifier);
        dbg('oauth_consume_code result: '.($row === false ? 'false' : json_encode($row)));
    } catch (Throwable $e) {
        dbg('FAIL: oauth_consume_code exception — '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if ($row === false) {
        dbg('FAIL: invalid_grant (PKCE path)');
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code invalid, expired, or PKCE mismatch']);
        exit;
    }

} else {
    if ($clientId === '' || $clientSecret === '') {
        dbg('FAIL: missing credentials');
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Missing credentials']);
        exit;
    }

    try {
        $client = oauth_client($clientId);
        dbg('Client found OK');
    } catch (Throwable $e) {
        dbg('FAIL: unknown client');
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Unknown client']);
        exit;
    }

    if (!hash_equals($client['client_secret'], $clientSecret)) {
        dbg('FAIL: bad secret');
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'error_description' => 'Bad secret']);
        exit;
    }

    try {
        $row = oauth_consume_code($code, $clientId, $redirectUri, $codeVerifier);
        dbg('oauth_consume_code result: '.($row === false ? 'false' : json_encode($row)));
    } catch (Throwable $e) {
        dbg('FAIL: oauth_consume_code exception — '.$e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }

    if ($row === false) {
        dbg('FAIL: invalid_grant (confidential path)');
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Code invalid, expired, or already used']);
        exit;
    }
}

// ── Issue access token ───────────────────────────────────────────────────────
try {
    $token = oauth_issue_token($clientId, (int) $row['user_id'], $row['scope']);
    dbg('Token issued OK — user_id='.$row['user_id']);
} catch (Throwable $e) {
    dbg('FAIL: oauth_issue_token — '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

$resp = [
    'access_token' => $token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600,
    'scope'        => $row['scope'],
];
dbg('SUCCESS: '.json_encode($resp));
echo json_encode($resp);
