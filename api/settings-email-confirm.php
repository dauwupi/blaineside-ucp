<?php
/**
 * GET /api/settings-email-confirm.php?token=…
 *
 * Opened from the link sent to the NEW address. Completes the change and
 * redirects into the profile page, the same way verify.php redirects into
 * /welcome — this endpoint is reached from a mail client, so it answers with
 * a redirect rather than JSON.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_mailer.php';

header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');

$token = (string)($_GET['token'] ?? '');
$base  = rtrim($CONFIG['site']['base_url'], '/');

function email_redirect(string $q, string $base): void {
    header('Location: ' . $base . '/profile?' . $q . '#settings', true, 302);
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    email_redirect('email=invalid', $base);
}

$pdo  = db();
$stmt = $pdo->prepare(
    'SELECT id, username, email, pending_email, pending_email_expires, status
       FROM ucp_accounts WHERE pending_email_token = ? LIMIT 1'
);
$stmt->execute([token_hash($token)]);
$acc = $stmt->fetch();

if (!$acc || empty($acc['pending_email'])) {
    email_redirect('email=used', $base);
}
if ($acc['status'] !== 'active') {
    email_redirect('email=invalid', $base);
}
if ((int)$acc['pending_email_expires'] < time()) {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL
          WHERE id = ?'
    )->execute([(int)$acc['id']]);
    email_redirect('email=expired', $base);
}

// Someone else may have claimed the address while the link sat in the inbox.
$clash = $pdo->prepare('SELECT id FROM ucp_accounts WHERE email_lower = ? AND id <> ? LIMIT 1');
$clash->execute([strtolower((string)$acc['pending_email']), (int)$acc['id']]);
if ($clash->fetch()) {
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL
          WHERE id = ?'
    )->execute([(int)$acc['id']]);
    email_redirect('email=taken', $base);
}

$old = (string)$acc['email'];
$new = (string)$acc['pending_email'];

$pdo->prepare(
    'UPDATE ucp_accounts
        SET email = ?, email_lower = ?,
            pending_email = NULL, pending_email_token = NULL, pending_email_expires = NULL
      WHERE id = ?'
)->execute([$new, strtolower($new), (int)$acc['id']]);

// The old inbox gets the last word: it is the only place a hijacked account
// can still be shouted at, and it stops being reachable after this point.
send_mail(
    $old, (string)$acc['username'],
    'Your BlaineSide UCP email has changed',
    email_changed_email_html((string)$acc['username'], $new),
    "Hi {$acc['username']}, the email on your BlaineSide UCP is now {$new}. " .
    "If this wasn't you, contact staff on Discord immediately."
);

require_once __DIR__ . '/_sessions.php';
security_log($pdo, (int)$acc['id'], 'email_changed',
    'Now ' . mask_email($new), 'good');

email_redirect('email=changed', $base);
