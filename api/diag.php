<?php
/**
 * GET /api/diag.php?key=<the value below>
 *
 * A read-only self-check for the login/session plumbing. It reports only
 * booleans and non-sensitive server values — never credentials, hashes,
 * tokens or personal data.
 *
 * Load it twice in a row: the second load proves whether PHP sessions
 * actually persist between requests on this host, which is the single
 * most common cause of "it logs me straight back out".
 *
 * DELETE THIS FILE once the UCP is behaving. It is harmless, but there
 * is no reason to leave a diagnostic reachable on a live site.
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Generated key. This file is a temporary diagnostic — delete it from the
// server once the UCP is behaving, since the key has been shared in chat.
const DIAG_KEY = 'bs-diag-31ce0fa72f527d45e2';

if (($_GET['key'] ?? '') !== DIAG_KEY) {
    http_response_code(404);
    exit;
}

require __DIR__ . '/_bootstrap.php';

$out = [];

/* ---- 1. Scheme, as each source sees it ------------------------------ */
$out['scheme'] = [
    'is_https_result'       => is_https(),
    'server_HTTPS'          => $_SERVER['HTTPS'] ?? null,
    'x_forwarded_proto'     => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
    'x_forwarded_ssl'       => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? null,
    'request_scheme'        => $_SERVER['REQUEST_SCHEME'] ?? null,
    'server_port'           => $_SERVER['SERVER_PORT'] ?? null,
    'note' => 'If you loaded this over http:// but is_https_result is true, '
            . 'cookies get the Secure flag and your browser throws them away. '
            . 'That is the bug.',
];

/* ---- 2. Session cookie params actually in force --------------------- */
$out['session'] = [
    'cookie_params' => session_get_cookie_params(),
    'name'          => session_name(),
    'id_present'    => session_id() !== '',
    'save_path_writable' => is_writable(session_save_path() ?: sys_get_temp_dir()),
    'gc_maxlifetime'     => (int)ini_get('session.gc_maxlifetime'),
];

/* ---- 3. Does the session survive between requests? ------------------ */
if (!isset($_SESSION['diag_seen'])) {
    $_SESSION['diag_seen'] = 1;
    $out['session']['persistence'] = 'first load — reload this page to test';
} else {
    $_SESSION['diag_seen']++;
    $out['session']['persistence'] = 'OK — session survived ' . $_SESSION['diag_seen'] . ' loads';
}

/* ---- 4. Schema the login code depends on ---------------------------- */
$need = [
    'remember_token', 'remember_expires',   // remember-me
    'reset_token', 'reset_expires',         // password reset
    'last_device',                          // "from this device" notice
];
try {
    $cols = [];
    foreach (db()->query('SHOW COLUMNS FROM ucp_accounts') as $r) {
        $cols[] = $r['Field'];
    }
    $missing = array_values(array_diff($need, $cols));
    $out['schema'] = [
        'ucp_accounts_ok'      => $missing === [],
        'missing_columns'      => $missing,
        'login_attempts_table' => (bool)db()->query("SHOW TABLES LIKE 'ucp_login_attempts'")->fetch(),
    ];
} catch (Throwable $e) {
    $out['schema'] = ['error' => 'could not read schema'];
}

/* ---- 5. Cookies the browser actually sent back ---------------------- */
$out['cookies_received'] = array_values(array_keys($_COOKIE));

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
