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
$stmt = $pdo->prepare('SELECT id, status, verify_expires FROM ucp_accounts WHERE verify_token = ? LIMIT 1');
$stmt->execute([$token]);
$acc = $stmt->fetch();

if (!$acc) {
    verify_redirect('/login?verify=used', $base);
}
if ($acc['status'] === 'active') {
    verify_redirect('/login?verify=already', $base);
}

// Only a PENDING account may be activated by a verification link. Previously
// anything that wasn't already active was flipped to active — so a SUSPENDED
// account that had never verified could un-suspend itself with an old email.
if ($acc['status'] !== 'pending') {
    verify_redirect('/login?verify=invalid', $base);
}

// Links expire. Without this a verification email stayed valid forever, so an
// old inbox (or a leaked mail archive) remained a permanent account key.
if (!empty($acc['verify_expires']) && (int)$acc['verify_expires'] < time()) {
    $pdo->prepare('UPDATE ucp_accounts SET verify_token = NULL, verify_expires = NULL WHERE id = ?')
        ->execute([$acc['id']]);
    verify_redirect('/login?verify=expired', $base);
}

$pdo->prepare("UPDATE ucp_accounts SET status = 'active', verify_token = NULL, verify_expires = NULL WHERE id = ?")
    ->execute([$acc['id']]);

verify_redirect('/welcome', $base);
