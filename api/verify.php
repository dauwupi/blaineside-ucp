<?php
/**
 * GET /api/verify.php?token=…
 * Clicked from the verification email. Activates the account,
 * then shows a branded confirmation page.
 *
 * Note: forum accounts are created automatically by IPS on the user's
 * first OAuth login — no need to pre-create them here.
 */
require __DIR__ . '/_bootstrap.php';

// This endpoint returns HTML (it's opened in a browser), not JSON.
header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');

$token = (string)($_GET['token'] ?? '');
$base  = rtrim($CONFIG['site']['base_url'], '/');

/**
 * The confirmation screens are real pages now (/welcome and /login),
 * so this endpoint just consumes the token and redirects into them.
 *   ok        -> /welcome                (the designed "You're in" page)
 *   already   -> login.html?verified=already
 *   bad/spent -> login.html?verify=invalid
 */
function verify_redirect(string $path, string $base): void {
    header('Location: ' . $base . '/' . ltrim($path, '/'), true, 302);
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    verify_redirect('/login?verify=invalid', $base);
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, status FROM ucp_accounts WHERE verify_token = ? LIMIT 1');
$stmt->execute([$token]);
$acc = $stmt->fetch();

if (!$acc) {
    verify_redirect('/login?verify=used', $base);
}
if ($acc['status'] === 'active') {
    verify_redirect('/login?verify=already', $base);
}

$pdo->prepare('UPDATE ucp_accounts SET status = "active", verify_token = NULL WHERE id = ?')
    ->execute([$acc['id']]);

verify_redirect('/welcome', $base);
