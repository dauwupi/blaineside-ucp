<?php
/**
 * OAuth2 shared helpers — loaded by authorize / token / userinfo endpoints.
 * Validates clients, generates codes/tokens, and looks them up.
 * Supports PKCE (RFC 7636) with S256 and plain methods.
 *
 * Include AFTER config + db() are available.
 */

declare(strict_types=1);

/** Return the client row or throw. */
function oauth_client(string $clientId): array {
    $stmt = db()->prepare(
        'SELECT client_id, client_secret, redirect_uri, name
           FROM ucp_oauth_clients WHERE client_id = ? LIMIT 1'
    );
    $stmt->execute([$clientId]);
    $c = $stmt->fetch();
    if (!$c) throw new RuntimeException('Unknown OAuth client.');
    return $c;
}

/** Verify that $uri starts with the registered redirect_uri. */
function oauth_check_redirect(string $registeredUri, string $requested): bool {
    return $requested === $registeredUri ||
           str_starts_with($requested, rtrim($registeredUri, '/') . '?') ||
           str_starts_with($requested, rtrim($registeredUri, '/') . '/');
}

/**
 * Issue a one-time authorisation code (10-minute TTL).
 * Pass $codeChallenge + $codeChallengeMethod when the client uses PKCE.
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
           (code, client_id, user_id, redirect_uri, scope, state, expires_at,
            code_challenge, code_challenge_method)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $code, $clientId, $userId, $redirectUri, $scope, $state, $expiresAt,
        $codeChallenge, $codeChallengeMethod,
    ]);
    return $code;
}

/**
 * Exchange a code for its row (marks code used; returns false on failure).
 *
 * If the code was issued with a PKCE challenge, pass $codeVerifier and this
 * function will verify it.  For confidential clients (no PKCE), leave it ''.
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

    if (!$row)                                                          return false;
    if ($row['used'])                                                   return false;
    if (new DateTime() > new DateTime($row['expires_at']))              return false;
    if ($row['redirect_uri'] !== $redirectUri)                          return false;

    // ── PKCE verification ────────────────────────────────────────────────────
    if ($row['code_challenge'] !== '') {
        if ($codeVerifier === '') return false; // challenge present but no verifier

        $method = strtolower($row['code_challenge_method']);
        if ($method === 's256') {
            $derived = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        } elseif ($method === 'plain' || $method === '') {
            $derived = $codeVerifier;
        } else {
            return false; // unsupported method
        }

        if (!hash_equals($row['code_challenge'], $derived)) return false;
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
