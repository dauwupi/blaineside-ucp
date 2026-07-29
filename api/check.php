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
    // Email existence is NOT public. Answering "taken" here handed anyone a
    // free "does this person have a BlaineSide account?" API — which also
    // undoes the deliberate non-disclosure in reset.php and resend.php.
    //
    // Usernames above are fine to answer: they're displayed publicly on the
    // forum, so nothing is revealed that isn't already visible.
    //
    // The form still gets a useful answer for a well-formed address; a
    // genuine collision surfaces on submit, where it's rate-limited and
    // needs a CSRF token. Requiring the token here too means enumeration
    // costs a session per burst instead of being a plain GET loop.
    require_csrf();
    throttle('check_email', 10);

    $e = trim((string)$_GET['email']);
    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) {
        ok(['available' => false, 'reason' => 'invalid']);
    }
    ok(['available' => true]);
}

fail('Nothing to check.');
