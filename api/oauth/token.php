<?php
/**
 * BlaineSide UCP — OAuth2 token endpoint
 * POST /api/oauth/token.php
 *
 * Accepts client credentials via:
 *   - HTTP Basic auth (Authorization: Basic base64(client_id:client_secret))  ← IPS uses this
 *   - POST body (client_id + client_secret)  ← fallback
 */

declare(strict_types=1);

require_once __DIR__ . '/../_bootstrap.php';
require_once __DIR__ . '/_client.php';

header('Content-Type: application/json');

// ── 1. Read client credentials (Basic auth takes priority) ──────────────────
if (!empty($_SERVER['PHP_AUTH_USER'])) {
    $clientId     = $_SERVER['PHP_AUTH_USER'];
    $clientSecret = $_SERVER['PHP_AUTH_PW'] ?? '';
} elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    // Some servers don't populate PHP_AUTH_USER; parse the header manually
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (stripos($auth, 'Basic ') === 0) {
        [$clientId, $clientSecret] = explode(':', base64_decode(substr($auth, 6)), 2) + ['', ''];
    } else {
        $clientId = $clientSecret = '';
    }
} else {
    $clientId     = $_POST['client_id']     ?? '';
    $clientSecret = $_POST['client_secret'] ?? '';
}

// ── 2. Validate grant type ───────────────────────────────────────────────────
$grantType = $_POST['grant_type'] ?? '';
if ($grantType !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'unsupported_grant_type']);
    exit;
}

// ── 3. Validate client ───────────────────────────────────────────────────────
if (empty($clientId) || empty($clientSecret)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Missing client credentials.']);
    exit;
}

$client = oauth_client($clientId);
if (!hash_equals($client['client_secret'], $clientSecret)) {
    http_response_code(401);
    echo json_encode(['error' => 'invalid_client', 'error_description' => 'Invalid client secret.']);
    exit;
}

// ── 4. Consume the authorization code ───────────────────────────────────────
$code        = $_POST['code']         ?? '';
$redirectUri = $_POST['redirect_uri'] ?? '';

$row = oauth_consume_code($code, $clientId, $redirectUri);
if ($row === false) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_grant', 'error_description' => 'Authorization code invalid, expired, or already used.']);
    exit;
}

// ── 5. Issue access token ────────────────────────────────────────────────────
$token = oauth_issue_token($clientId, (int) $row['user_id'], $row['scope']);

echo json_encode([
    'access_token' => $token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600,
    'scope'        => $row['scope'],
]);
