<?php
/**
 * POST /api/oauth/token.php
 *
 * OAuth2 Token endpoint — server-to-server, called by IPS after code exchange.
 * Accepts: grant_type=authorization_code, code, client_id, client_secret, redirect_uri
 * Returns: { access_token, token_type, expires_in, scope }
 *
 * IPS ACP: set Token Endpoint to https://ucp.blaineside.com/api/oauth/token.php
 */

declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require __DIR__ . '/_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required.', 405);

// Accept form-encoded OR JSON body
$in          = read_input();
$grantType   = trim((string)($in['grant_type']    ?? ''));
$code        = trim((string)($in['code']           ?? ''));
$clientId    = trim((string)($in['client_id']      ?? ''));
$clientSecret= trim((string)($in['client_secret']  ?? ''));
$redirectUri = trim((string)($in['redirect_uri']   ?? ''));

// Also accept HTTP Basic auth (some OAuth stacks send it that way)
if (!$clientId && isset($_SERVER['PHP_AUTH_USER'])) {
    $clientId     = $_SERVER['PHP_AUTH_USER'];
    $clientSecret = $_SERVER['PHP_AUTH_PW'] ?? '';
}

if ($grantType !== 'authorization_code') fail('Unsupported grant_type.', 400);
if (!$code || !$clientId || !$clientSecret || !$redirectUri) fail('Missing required parameters.', 400);

// Verify client
$client = oauth_client($clientId);
if (!hash_equals($client['client_secret'], $clientSecret)) fail('Invalid client_secret.', 401);

// Exchange the code
$row = oauth_consume_code($code, $clientId, $redirectUri);
if (!$row) fail('Invalid, expired, or already-used authorisation code.', 400);

// Issue access token
$token  = oauth_issue_token($clientId, (int)$row['user_id'], $row['scope']);

json_out([
    'access_token' => $token,
    'token_type'   => 'Bearer',
    'expires_in'   => 3600,
    'scope'        => $row['scope'],
]);
