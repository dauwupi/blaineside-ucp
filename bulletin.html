<?php
/**
 * GET /api/check.php?username=…  OR  ?email=…
 * Used by the registration form's live availability checks.
 * Returns { ok:true, available:bool }.
 */
require __DIR__ . '/_bootstrap.php';
throttle('check', 40);

$pdo = db();

if (isset($_GET['username'])) {
    $u = trim((string)$_GET['username']);
    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $u)) {
        ok(['available' => false, 'reason' => 'invalid']);
    }
    // Reserved names that can't be claimed.
    $reserved = ['admin','administrator','owner','blaineside','staff','moderator','support','root','system','noreply'];
    if (in_array(strtolower($u), $reserved, true)) {
        ok(['available' => false, 'reason' => 'reserved']);
    }
    $stmt = $pdo->prepare('SELECT 1 FROM ucp_accounts WHERE username_lower = ? LIMIT 1');
    $stmt->execute([strtolower($u)]);
    ok(['available' => !$stmt->fetch()]);
}

if (isset($_GET['email'])) {
    $e = trim((string)$_GET['email']);
    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
        ok(['available' => false, 'reason' => 'invalid']);
    }
    $stmt = $pdo->prepare('SELECT 1 FROM ucp_accounts WHERE email_lower = ? LIMIT 1');
    $stmt->execute([strtolower($e)]);
    ok(['available' => !$stmt->fetch()]);
}

fail('Nothing to check.');
