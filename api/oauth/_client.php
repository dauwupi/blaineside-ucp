<?php
/**
 * OAuth2 shared helpers — loaded by authorize / token / userinfo endpoints.
 * Validates clients, generates codes/tokens, and looks them up.
 *
 * Include AFTER a file that defines db() and $CONFIG.
 * NOTE: oauth_client() throws RuntimeException on failure (not fail()) so it
 * is safe to use from standalone endpoints that don't include _bootstrap.php.
 */

declare(strict_types=1);

/** Return the client row or throw RuntimeException. */
function oauth_client(string $clientId): array {
    $stmt = db()->prepare(
        'SELECT client_id, client_secret, redirect_uri, name
           FROM ucp_oauth_clients WHERE client_id = ? LIMIT 1'
    );
    $stmt->execute([$clientId]);
    $c = $stmt->fetch();
    if (!$c) throw new RuntimeException('Unknown OAuth client: ' . $clientId);
    return $c;
}

/** Verify that $uri matches the registered redirect_uri. */
function oauth_check_redirect(string $registeredUri, string $requested): bool {
    // Exact match or same base with a query string appended.
    return $requested === $registeredUri ||
           str_starts_with($requested, rtrim($registeredUri, '/') . '?');
}

/**
 * Issue a one-time authorisation code (10-minute TTL).
 * Stores PKCE challenge if provided by the client.
 */
function oauth_issue_code(
    string $clientId,
    int    $userId,
    string $redirectUri,
    string $scope,
    string $state,
    string $codeChallenge       = '',
    string $codeChallengeMethod = ''
): string {
    $code      = bin2hex(random_bytes(32)); // 64 hex chars
    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    db()->prepare(
        'INSERT INTO ucp_oauth_codes
           (code, client_id, user_id, redirect_uri, scope, state,
            code_challenge, code_challenge_method, expires_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $code, $clientId, $userId, $redirectUri, $scope, $state,
        $codeChallenge, $codeChallengeMethod, $expiresAt,
    ]);
    return $code;
}

/**
 * Exchange a code for a user row (marks code used; returns false on any failure).
 * Performs PKCE S256/plain verification if the code has a stored challenge.
 */
function oauth_consume_code(
    string $code,
    string $clientId,
    string $redirectUri,
    string $codeVerifier = ''
): array|false {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT user_id, redirect_uri, scope, expires_at, used,
                code_challenge, code_challenge_method
           FROM ucp_oauth_codes
          WHERE code = ? AND client_id = ? LIMIT 1'
    );
    $stmt->execute([$code, $clientId]);
    $row = $stmt->fetch();

    if (!$row)                                                    return false;
    if ($row['used'])                                             return false;
    if (new DateTime() > new DateTime($row['expires_at']))        return false;
    if ($row['redirect_uri'] !== $redirectUri)                    return false;

    // ── PKCE verification ────────────────────────────────────────────────────
    $challenge = $row['code_challenge'] ?? '';
    if ($challenge !== '') {
        // A challenge was stored — verifier is mandatory.
        if ($codeVerifier === '') return false;

        $method = strtolower($row['code_challenge_method'] ?? '');
        if ($method === 's256' || $method === '') {
            // Default / explicit S256
            $derived = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        } elseif ($method === 'plain') {
            $derived = $codeVerifier;
        } else {
            return false; // Unknown method
        }

        if (!hash_equals($challenge, $derived)) return false;
    }

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
