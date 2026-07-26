<?php
/**
 * GET /api/verify.php?token=…
 * Clicked from the verification email. Activates the account, then shows a
 * small branded page and sends the user to the login screen.
 */
require __DIR__ . '/_bootstrap.php';

// This endpoint returns HTML (it's opened in a browser), not JSON.
header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');

$token = (string)($_GET['token'] ?? '');
$base  = rtrim($CONFIG['site']['base_url'], '/');

function verify_page(string $title, string $body, string $base): void {
    echo <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>$title — BlaineSide UCP</title>
<style>
  body{margin:0;height:100vh;display:grid;place-items:center;background:#100f0e;
    font-family:Inter,system-ui,sans-serif;color:#f1efe9}
  .card{max-width:420px;text-align:center;background:#1a1815;border:1px solid #26221e;
    border-radius:14px;padding:34px 30px}
  h1{font-size:20px;margin:0 0 10px}
  p{font-size:14px;line-height:1.6;color:#c9bea9;margin:0 0 20px}
  a{display:inline-block;background:linear-gradient(145deg,#e2b65c,#d4923a);color:#1a1206;
    font-weight:700;text-decoration:none;padding:12px 22px;border-radius:10px}
  .wm{font-family:Oswald,sans-serif;font-weight:700;letter-spacing:2px;margin-bottom:18px;color:#f1efe9}
  .wm b{color:#e2b65c}
</style></head><body>
  <div class="card">
    <div class="wm">BLAINE<b>SIDE</b></div>
    <h1>$title</h1>
    <p>$body</p>
    <a href="$base/login.html">Go to sign in</a>
  </div>
</body></html>
HTML;
    exit;
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    verify_page('Invalid link', 'This verification link is malformed or incomplete.', $base);
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT id, status FROM ucp_accounts WHERE verify_token = ? LIMIT 1');
$stmt->execute([$token]);
$acc = $stmt->fetch();

if (!$acc) {
    verify_page('Link expired', 'This link is invalid or has already been used. Try signing in — your account may already be active.', $base);
}
if ($acc['status'] === 'active') {
    verify_page('Already verified', 'Your account is already active. You can sign in now.', $base);
}

$upd = $pdo->prepare('UPDATE ucp_accounts SET status = "active", verify_token = NULL WHERE id = ?');
$upd->execute([$acc['id']]);

verify_page('Email verified ✓', 'Your BlaineSide UCP account is now active. Welcome aboard — you can sign in.', $base);
