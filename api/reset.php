<?php
/**
 * POST /api/reset.php
 * Body: { email }
 *
 * Starts the "forgot password" flow. Always returns the same neutral response
 * whether or not the address belongs to an account — the page copy already
 * says "if that address belongs to a UCP…", so we must not reveal existence.
 *
 * On a real, active account we mint a single-use token (30-minute expiry,
 * matching the copy on the page) and email a link to /reset-confirm.
 */

declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('reset', 5);

$in    = read_input();
$email = trim((string)($in['email'] ?? ''));

if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 190) {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT id, username, email, status FROM ucp_accounts WHERE email_lower = ? LIMIT 1'
    );
    $stmt->execute([strtolower($email)]);
    $acc = $stmt->fetch();

    // Only active accounts get a link. Pending accounts still need to verify
    // their email first, so a reset would be meaningless.
    if ($acc && $acc['status'] === 'active') {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + 1800; // 30 minutes — keep in sync with the page copy

        $pdo->prepare('UPDATE ucp_accounts SET reset_token = ?, reset_expires = ? WHERE id = ?')
            ->execute([token_hash($token), $expires, (int)$acc['id']]);

        $link = rtrim($CONFIG['site']['base_url'], '/')
              . '/reset-confirm?token=' . urlencode($token);

        send_mail(
            $acc['email'], $acc['username'],
            'Reset your BlaineSide UCP password',
            password_reset_email_html($acc['username'], $link),
            "Hi {$acc['username']}, reset your BlaineSide password (link valid 30 minutes): $link"
        );
    }
}

// Identical response in every case — existence is never revealed.
ok(['message' => 'If that address belongs to a UCP, a reset link is on its way.']);
