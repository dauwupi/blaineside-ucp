<?php
/**
 * OAuth2 shared helpers — loaded by authorize / token / userinfo endpoints.
 * Validates clients, generates codes/tokens, and looks them up.
 *
 * Include AFTER _bootstrap.php (needs $CONFIG and db()).
 */

declare(strict_types=1);

// Ensure fail() is always available in this context
if (!function_exists('fail')) {
    function fail(string $msg, int $code = 400): never {
        throw new RuntimeException($msg, $code);
    }
}

/** Return the client row or throw. */
function oauth_client(string $clientId): array {
    $stmt = db()->prepare(
        'SELECT client_id, client_secret, redirect_uri, name
           FROM ucp_oauth_clients WHERE client_id = ? LIMIT 1'
    );
    $stmt->execute([$clientId]);
    $c = $stmt->fetch();
    if (!$c) throw new RuntimeException('Unknown OAuth client: ' . $clientId, 401);
    return $c;
}

/** Verify that $uri starts with the registered redirect_uri. */
function oauth_check_redirect(string $registeredUri, string $requested): bool {
    // Allow exact match or — for multi-path flexibility — same scheme+host+path prefix.
    return $requested === $registeredUri ||
           str_starts_with($requested, rtrim($registeredUri, '/') . '?') ||
           str_starts_with($requested, rtrim($registeredUri, '/') . '/');
}

/** Issue a one-time authorisation code (10-minute TTL). */
function oauth_issue_code(
    string $clientId,
    int    $userId,
    string $redirectUri,
    string $scope,
    string $state
): string {
    $code      = bin2hex(random_bytes(32)); // 64 hex chars
    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    db()->prepare(
        'INSERT INTO ucp_oauth_codes
           (code, client_id, user_id, redirect_uri, scope, state, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([$code, $clientId, $userId, $redirectUri, $scope, $state, $expiresAt]);
    return $code;
}

/** Exchange a code for a user_id (marks code used; returns false on failure). */
function oauth_consume_code(
    string $code,
    string $clientId,
    string $redirectUri = ''
): array|false {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT user_id, redirect_uri, scope, expires_at, used
           FROM ucp_oauth_codes
          WHERE code = ? AND client_id = ? LIMIT 1'
    );
    $stmt->execute([$code, $clientId]);
    $row = $stmt->fetch();

    if (!$row)                            return false; // not found
    if ($row['used'])                     return false; // already used
    if (new DateTime() > new DateTime($row['expires_at'])) return false; // expired
    // Only check redirect_uri if the token request actually provided one
    if ($redirectUri !== '' && $row['redirect_uri'] !== $redirectUri) return false; // mismatch

    // Mark used immediately (single-use guarantee)
    $pdo->prepare('UPDATE ucp_oauth_codes SET used = 1 WHERE code = ?')
        ->execute([$code]);

    return $row;
}

/** Issue a Bearer access token (1-hour TTL). */
function oauth_issue_token(string $clientId, int $userId, string $scope): string {
    $token     = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    db()->prepare(
        'INSERT INTO ucp_oauth_tokens (token, client_id, user_id, scope, expires_at)
         VALUES (?, ?, ?, ?, ?)'
    )->execute([$token, $clientId, $userId, $scope, $expiresAt]);
    return $token;
}

/** Validate a Bearer token; returns the row or false. */
function oauth_validate_token(string $token): array|false {
    $stmt = db()->prepare(
        'SELECT user_id, scope, expires_at
           FROM ucp_oauth_tokens
          WHERE token = ? LIMIT 1'
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (new DateTime() > new DateTime($row['expires_at'])) return false;
    return $row;
}
