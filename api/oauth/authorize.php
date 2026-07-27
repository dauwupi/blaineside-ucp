<?php
/**
 * GET/POST /api/oauth/authorize.php
 *
 * OAuth2 Authorization Code endpoint — browser-facing, outputs HTML.
 *
 * GET  → validate request → if not logged in, redirect to UCP login → show confirm page
 * POST → user clicked "Authorise" → issue code → redirect to IPS
 * POST → user clicked "Cancel"   → redirect with error=access_denied
 *
 * IPS ACP: set Authorization Endpoint to https://ucp.blaineside.com/api/oauth/authorize.php
 */

declare(strict_types=1);

// ── Minimal bootstrap (no JSON header — this page outputs HTML) ───────────────
$configPath = dirname(__DIR__) . '/config.php';
if (!file_exists($configPath)) { http_response_code(500); die('Server not configured.'); }
$CONFIG = require $configPath;

session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
]);
session_name('BSUCP');
session_start();

require __DIR__ . '/_client.php';   // oauth helpers (needs db() — defined below)

// Inline the db() helper (bootstrap.php's version requires its JSON headers)
$__PDO = null;
function db(): PDO {
    global $CONFIG, $__PDO;
    if ($__PDO instanceof PDO) return $__PDO;
    $c   = $CONFIG['db'];
    $dsn = "mysql:host={$c['host']};dbname={$c['name']};charset={$c['charset']}";
    $__PDO = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $__PDO;
}

// ── Helper: show an error page ────────────────────────────────────────────────
function html_error(string $msg): never {
    header('Content-Type: text/html; charset=utf-8');
    http_response_code(400);
    echo "<!doctype html><html><head><meta charset=utf-8><title>Error — BlaineSide</title></head>
    <body style='font-family:system-ui;background:#100f0e;color:#f1efe9;display:grid;place-items:center;min-height:100vh;margin:0'>
    <div style='text-align:center;max-width:400px;padding:40px'>
    <p style='color:#d4923a;font-size:13px;font-weight:700;letter-spacing:.1em;text-transform:uppercase'>OAuth Error</p>
    <h1 style='margin:10px 0 16px;font-size:24px'>".htmlspecialchars($msg)."</h1>
    <a href='https://ucp.blaineside.com' style='color:#e2b65c'>← Back to UCP</a>
    </div></body></html>";
    exit;
}

// ── Read incoming OAuth params ────────────────────────────────────────────────
$responseType        = $_GET['response_type']         ?? $_POST['response_type']         ?? '';
$clientId            = $_GET['client_id']             ?? $_POST['client_id']             ?? '';
$redirectUri         = $_GET['redirect_uri']           ?? $_POST['redirect_uri']           ?? '';
$scope               = $_GET['scope']                  ?? $_POST['scope']                  ?? 'openid';
$state               = $_GET['state']                  ?? $_POST['state']                  ?? '';
$codeChallenge       = $_GET['code_challenge']         ?? $_POST['code_challenge']         ?? '';
$codeChallengeMethod = $_GET['code_challenge_method']  ?? $_POST['code_challenge_method']  ?? '';

// ── Resume pending OAuth request after login redirect (restore params first) ──
if (isset($_GET['resume']) && !empty($_SESSION['oauth_pending'])) {
    $p = $_SESSION['oauth_pending'];
    unset($_SESSION['oauth_pending']);
    $clientId            = $p['clientId'];
    $redirectUri         = $p['redirectUri'];
    $scope               = $p['scope'];
    $state               = $p['state'];
    $responseType        = $p['responseType']        ?? 'code';
    $codeChallenge       = $p['codeChallenge']       ?? '';
    $codeChallengeMethod = $p['codeChallengeMethod'] ?? '';
}

// ── Validate params ───────────────────────────────────────────────────────────
if ($responseType !== 'code') html_error('Unsupported response_type. Expected: code');
if (!$clientId)               html_error('Missing client_id.');
if (!$redirectUri)            html_error('Missing redirect_uri.');

// Load & verify the client
try {
    $client = oauth_client($clientId);
} catch (Throwable) {
    html_error('Unknown client_id.');
}

// Verify redirect_uri matches registration
if (!oauth_check_redirect($client['redirect_uri'], $redirectUri)) {
    html_error('redirect_uri mismatch.');
}

// ── Auth check — if not logged in, send to UCP login ─────────────────────────
if (empty($_SESSION['uid'])) {
    // Store OAuth params in session so we can resume after login
    $_SESSION['oauth_pending'] = compact('clientId','redirectUri','scope','state','responseType','codeChallenge','codeChallengeMethod');
    $loginUrl = $CONFIG['site']['base_url'] . '/login.html?next=oauth_resume';
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load user ─────────────────────────────────────────────────────────────────
$userId   = (int)$_SESSION['uid'];
$userName = $_SESSION['name'] ?? '';
$initial  = strtoupper(substr($userName, 0, 1));

// Refresh from DB (status check)
$stmt = db()->prepare('SELECT username, status FROM ucp_accounts WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$acc = $stmt->fetch();
if (!$acc || $acc['status'] !== 'active') {
    session_destroy();
    header('Location: ' . $CONFIG['site']['base_url'] . '/login.html');
    exit;
}
$userName = $acc['username'];
$initial  = strtoupper(substr($userName, 0, 1));

// ── Handle form POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        $sep = str_contains($redirectUri, '?') ? '&' : '?';
        header('Location: ' . $redirectUri . $sep . 'error=access_denied&state=' . urlencode($state));
        exit;
    }

    if ($action === 'authorise') {
        // Verify CSRF token
        if (!hash_equals($_SESSION['oauth_csrf'] ?? '', $_POST['csrf'] ?? '')) {
            html_error('Invalid form token. Please try again.');
        }

        $code = oauth_issue_code($clientId, $userId, $redirectUri, $scope, $state, $codeChallenge, $codeChallengeMethod);
        $sep  = str_contains($redirectUri, '?') ? '&' : '?';
        header('Location: ' . $redirectUri . $sep . 'code=' . urlencode($code) . '&state=' . urlencode($state));
        exit;
    }
}

// ── Generate CSRF token for the form ─────────────────────────────────────────
$csrf = bin2hex(random_bytes(16));
$_SESSION['oauth_csrf'] = $csrf;

// ── Render the authorise page ─────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Authorise Sign-In — BlaineSide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Bitter:wght@600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --amber:#d4923a;--gold:#e2b65c;--parch:#f1efe9;
  --surface:#1a1816;--surface-2:#221f1c;--surface-3:#2a2621;
  --border:#332e27;--line:#282420;--text-dim:#a49a8c;--text-faint:#7d7466;
  --bg:#141210;
}
*{margin:0;padding:0;box-sizing:border-box}
html,body{min-height:100%}
body{background:var(--bg);color:var(--parch);font-family:'Inter',system-ui,sans-serif;
  line-height:1.55;-webkit-font-smoothing:antialiased;min-height:100vh;
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:40px 24px;position:relative;overflow-x:hidden}
a{color:inherit;text-decoration:none}
::selection{background:var(--amber);color:#1a1206}

.bg-layer{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none}
.bg-layer::before{content:"";position:absolute;left:50%;bottom:-30%;transform:translateX(-50%);
  width:120vw;height:80vh;
  background:radial-gradient(ellipse at center,rgba(212,146,58,.20) 0%,rgba(212,146,58,.07) 34%,transparent 62%);
  filter:blur(6px)}
.bg-layer::after{content:"";position:absolute;inset:0;
  background:radial-gradient(80% 60% at 85% 0%,rgba(226,182,92,.08),transparent 55%),
    linear-gradient(180deg,#181613 0%,#141210 55%,#100e0c 100%)}
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.5;
  background-image:linear-gradient(rgba(241,239,233,.022) 1px,transparent 1px),
    linear-gradient(90deg,rgba(241,239,233,.022) 1px,transparent 1px);
  background-size:64px 64px;
  -webkit-mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 78%);
  mask-image:radial-gradient(ellipse 80% 70% at 50% 45%,#000 30%,transparent 78%)}
.bg-vignette{position:fixed;inset:0;z-index:0;pointer-events:none;
  box-shadow:inset 0 0 220px 40px rgba(8,7,6,.7)}

.brand{position:relative;z-index:2;margin-bottom:26px;text-align:center}
.brand .name{font-family:'Oswald',sans-serif;font-weight:700;font-size:40px;
  letter-spacing:.06em;text-transform:uppercase;line-height:1;
  filter:drop-shadow(0 4px 22px rgba(0,0,0,.6))}
.brand .name .b1{color:var(--parch)}
.brand .name .b2{background:linear-gradient(100deg,var(--gold) 0%,var(--amber) 55%,#c07f2c 100%);
  -webkit-background-clip:text;background-clip:text;color:transparent}
.brand .tagline{margin-top:11px;font-family:'Oswald',sans-serif;font-size:11px;font-weight:500;
  letter-spacing:.34em;text-transform:uppercase;color:var(--text-faint)}

.card{position:relative;z-index:2;width:100%;max-width:440px;
  background:var(--surface);border:1px solid var(--border);border-radius:20px;
  box-shadow:0 34px 80px -38px rgba(0,0,0,.9);overflow:hidden;
  animation:rise .6s cubic-bezier(.16,.84,.3,1) both}
@keyframes rise{from{opacity:0;transform:translateY(14px) scale(.985);filter:blur(4px)}
  to{opacity:1;transform:none;filter:none}}
.card-body{padding:34px 34px 30px}
.kicker{font-family:'Oswald',sans-serif;font-size:11px;font-weight:600;letter-spacing:.26em;
  text-transform:uppercase;color:var(--amber);margin-bottom:12px}
h1{font-family:'Bitter',serif;font-weight:800;font-size:25px;
  letter-spacing:-.02em;line-height:1.2;margin-bottom:10px}
.intro{font-size:13.5px;color:var(--text-dim);line-height:1.55;margin-bottom:24px}
.intro .dom{color:var(--parch);font-weight:600}

.who{display:flex;align-items:center;gap:14px;padding:15px 16px;margin-bottom:20px;
  background:linear-gradient(180deg,var(--surface-2),rgba(34,31,28,.55));
  border:1px solid var(--border);border-radius:15px;position:relative;overflow:hidden}
.who::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;
  background:linear-gradient(180deg,var(--gold),var(--amber))}
.who .avatar{width:48px;height:48px;border-radius:13px;flex:none;
  display:grid;place-items:center;font-family:'Oswald',sans-serif;font-weight:700;font-size:21px;color:#1a1206;
  background:linear-gradient(150deg,var(--gold),var(--amber));
  box-shadow:0 6px 16px -6px rgba(212,146,58,.6),inset 0 0 0 1px rgba(255,240,200,.35)}
.who .meta{flex:1;min-width:0}
.who .meta .n{font-weight:700;font-size:16.5px;letter-spacing:-.01em;line-height:1.2}
.who .meta .sub{font-size:12px;color:var(--text-faint);margin-top:3px}
.who .switch{flex:none;font-size:12.5px;font-weight:600;color:var(--amber);
  padding:7px 13px;border-radius:9px;border:1px solid var(--border);
  background:rgba(212,146,58,.06);transition:.16s}
.who .switch:hover{background:rgba(212,146,58,.14);border-color:rgba(212,146,58,.3)}

.facts{border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:14px}
.fact{display:flex;align-items:baseline;gap:14px;padding:13px 16px}
.fact+.fact{border-top:1px solid var(--line)}
.fact .k{flex:none;width:96px;font-size:11px;font-weight:600;
  letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint)}
.fact .v{font-size:13.5px;color:var(--parch);font-weight:500;line-height:1.45}
.fact .v em{font-style:normal;color:var(--text-dim);font-weight:400}

.note{font-size:12px;color:var(--text-faint);line-height:1.55;margin-bottom:4px}
.note a{color:var(--amber);font-weight:600}

.actions{display:flex;gap:11px;margin-top:24px}
.btn{font-family:inherit;font-size:15px;font-weight:700;border-radius:12px;cursor:pointer;
  border:1px solid transparent;transition:transform .12s,filter .16s,background .16s;padding:15px}
.btn:active{transform:translateY(1px)}
.btn.primary{flex:1;background:linear-gradient(145deg,var(--gold),var(--amber));color:#1a1206;
  box-shadow:0 10px 24px -8px rgba(212,146,58,.6)}
.btn.primary:hover{filter:brightness(1.06)}
.btn.cancel{flex:0 0 auto;padding:15px 22px;background:transparent;
  border-color:var(--border);color:var(--text-dim)}
.btn.cancel:hover{background:var(--surface-2);color:var(--parch)}

@media(max-width:480px){.brand .name{font-size:34px}.card-body{padding:26px 24px}}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style>
</head>
<body>
  <div class="bg-layer"></div>
  <div class="bg-grid"></div>
  <div class="bg-vignette"></div>

  <div class="brand">
    <div class="name"><span class="b1">Blaine</span><span class="b2">Side</span></div>
    <div class="tagline">Forum Access</div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="kicker">Authorise Sign-In</div>
      <h1>Continue to the forums</h1>
      <p class="intro"><span class="dom">forum.blaineside.com</span> is signing you in with your BlaineSide account.</p>

      <div class="who">
        <div class="avatar"><?= htmlspecialchars($initial) ?></div>
        <div class="meta">
          <div class="n"><?= htmlspecialchars($userName) ?></div>
          <div class="sub">Signed in to your UCP</div>
        </div>
        <a class="switch" href="<?= htmlspecialchars($CONFIG['site']['base_url']) ?>/api/logout.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Switch</a>
      </div>

      <div class="facts">
        <div class="fact">
          <span class="k">Forum name</span>
          <span class="v"><?= htmlspecialchars($userName) ?> <em>(your UCP name)</em></span>
        </div>
        <div class="fact">
          <span class="k">Shared</span>
          <span class="v">Display name &amp; account status</span>
        </div>
        <div class="fact">
          <span class="k">Not shared</span>
          <span class="v">Password &amp; personal details</span>
        </div>
      </div>

      <p class="note">Your UCP name is used to log in and as your forum name — posts stay tied to your account. You can revoke forum access anytime under <a href="<?= htmlspecialchars($CONFIG['site']['base_url']) ?>/dashboard.html">Connected Sites</a>.</p>

      <form method="POST">
        <input type="hidden" name="client_id"     value="<?= htmlspecialchars($clientId) ?>">
        <input type="hidden" name="redirect_uri"  value="<?= htmlspecialchars($redirectUri) ?>">
        <input type="hidden" name="scope"         value="<?= htmlspecialchars($scope) ?>">
        <input type="hidden" name="state"         value="<?= htmlspecialchars($state) ?>">
        <input type="hidden" name="response_type" value="code">
        <input type="hidden" name="csrf"          value="<?= htmlspecialchars($csrf) ?>">
        <div class="actions">
          <button type="submit" name="action" value="cancel"    class="btn cancel">Cancel</button>
          <button type="submit" name="action" value="authorise" class="btn primary">Authorise &amp; continue</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
