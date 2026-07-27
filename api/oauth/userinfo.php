<?php
/**
 * GET /api/oauth/userinfo.php
 *
 * OpenID Connect UserInfo endpoint — called by IPS with Bearer token.
 * Returns the UCP account info IPS uses to create/match the forum member.
 *
 * IPS ACP: set User Profile URL to https://ucp.blaineside.com/api/oauth/userinfo.php
 * IPS ACP: Username field  → name (or preferred_username)
 *          Email field      → email
 *          Unique ID field  → sub
 */

declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require __DIR__ . '/_client.php';

// Extract Bearer token from Authorization header
$auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (str_starts_with($auth, 'Bearer ')) {
    $token = trim(substr($auth, 7));
}
if (!$token) fail('Missing Bearer token.', 401);

// Validate token
$row = oauth_validate_token($token);
if (!$row) fail('Token invalid or expired.', 401);

// Load user
$stmt = db()->prepare(
    'SELECT id, username, email FROM ucp_accounts WHERE id = ? AND status = "active" LIMIT 1'
);
$stmt->execute([$row['user_id']]);
$user = $stmt->fetch();
if (!$user) fail('User not found or inactive.', 401);

// OpenID Connect standard claims
json_out([
    'sub'                => (string)$user['id'],    // stable unique identifier
    'name'               => $user['username'],       // IPS display name
    'preferred_username' => $user['username'],
    'email'              => $user['email'],
    'email_verified'     => true,                   // UCP verified on registration
]);
