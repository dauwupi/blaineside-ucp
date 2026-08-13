<?php
/**
 * POST /api/discord-unlink.php
 *
 * Detaches the Discord account. No password is asked for: unlinking grants
 * nobody anything, and the honest failure mode — a player unlinking by
 * accident — is fixed by pressing Link again. CSRF still applies.
 *
 * The self-entered `discord` value from sign-up is left alone, so someone who
 * unlinks falls back to what they originally typed rather than to nothing.
 */
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_2fa.php';
require_once __DIR__ . '/_sessions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('POST required', 405);
require_csrf();
throttle('discord_unlink', 10);

$pdo = db();
$acc = current_account($pdo);
$uid = (int)$acc['id'];

if (empty($acc['discord_id'])) {
    json_out(['ok' => false, 'error' => 'No Discord account is linked.'], 400);
}

$was = (string)($acc['discord_username'] ?? 'a Discord account');

$pdo->prepare(
    'UPDATE ucp_accounts
        SET discord_id = NULL, discord_username = NULL, discord_linked_at = NULL
      WHERE id = ?'
)->execute([$uid]);

security_log($pdo, $uid, 'discord_unlinked', $was, 'warn');

ok(['message' => $was . ' has been unlinked.']);
