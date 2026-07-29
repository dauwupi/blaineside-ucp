<?php
/**
 * POST /api/resend.php
 * Body: { email }
 * Resends the verification link for a still-pending account.
 * Always responds success (doesn't reveal whether the email exists).
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('resend', 4);

$in    = read_input();
$email = trim((string)($in['email'] ?? ''));

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $pdo  = db();
    $stmt = $pdo->prepare('SELECT id, username, status, verify_token FROM ucp_accounts WHERE email_lower = ? LIMIT 1');
    $stmt->execute([strtolower($email)]);
    $acc = $stmt->fetch();

    if ($acc && $acc['status'] === 'pending') {
        // Reissue a fresh token each time.
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE ucp_accounts SET verify_token = ?, verify_expires = ? WHERE id = ?')->execute([token_hash($token), time() + 172800, $acc['id']]);
        $link = rtrim($CONFIG['site']['base_url'], '/') . '/api/verify.php?token=' . urlencode($token);
        send_mail($email, $acc['username'], 'Verify your BlaineSide UCP account',
            verification_email_html($acc['username'], $link),
            "Verify your BlaineSide account: $link");
    }
}

// Uniform response regardless.
ok(['message' => 'If that email needs verifying, we\'ve sent a new link.']);
