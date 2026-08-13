<?php
/**
 * BlaineSide UCP — everything that happens once a sign-in is fully authorised.
 *
 * This used to live inline at the bottom of login.php. Two-factor sign-in
 * splits authorisation across two requests — password in login.php, code in
 * 2fa-verify.php — and BOTH have to establish the session identically. Copying
 * ~120 lines into the second endpoint would guarantee they drift: the remember
 * -me token, the device cookie behind the "last sign-in" notice, the
 * last_login stamp and the lazy IPS member lookup would all silently stop
 * happening for anyone with 2FA on.
 *
 * Include AFTER _bootstrap.php and _ranks.php. Leading underscore keeps it
 * unreachable over HTTP.
 */

declare(strict_types=1);

/**
 * Establishes the signed-in session and emits the JSON the login page expects.
 * Does not return — it ends in ok().
 *
 * @param array $acc   Row with id, username, admin_rank, session_epoch.
 * @param array $extra Additional keys merged into the JSON response.
 */
function login_finish(PDO $pdo, array $acc, bool $remember, array $extra = [],
                      string $method = 'password'): void
{
    global $CONFIG;

    $uid  = (int)$acc['id'];
    $rank = (int)$acc['admin_rank'];
    $ip   = client_ip();

    // Password and (where required) second factor have both passed — drop any
    // lockout counters for this account.
    clear_failures($pdo, $uid, $ip, '');
    clear_failures($pdo, $uid, $ip, '2fa');

    // A brand new session id at the moment privilege is granted, so a session
    // fixed by an attacker before sign-in is not the one that ends up logged in.
    session_regenerate_id(true);

    // The half-authenticated state is finished with; leaving it behind would
    // let a later request re-enter the 2FA step with a stale identity.
    unset(
        $_SESSION['pending_2fa'],
        $_SESSION['pending_2fa_exp'],
        $_SESSION['pending_2fa_tries'],
        $_SESSION['pending_remember'],
        $_SESSION['pending_name']
    );

    $_SESSION['uid']      = $uid;
    $_SESSION['name']     = $acc['username'];
    $_SESSION['rank']     = $rank;
    $_SESSION['epoch']    = (int)($acc['session_epoch'] ?? 0);
    $_SESSION['remember'] = $remember;

    // ---- Device list + activity log ----------------------------------------
    // Both are best-effort by design (see _sessions.php): a sign-in that has
    // already been authorised must not fail because a logging table is missing.
    require_once __DIR__ . '/_sessions.php';
    session_begin($pdo, $uid, $remember);
    security_log($pdo, $uid, 'signin', [
        'password' => 'Password only',
        'totp'     => 'Password and a code from your authenticator app',
        'backup'   => 'Password and a recovery code',
    ][$method] ?? 'Password only', $method === 'password' ? 'info' : 'good');

    // ---- Remember me --------------------------------------------------------
    // Must never be able to break a sign-in that has already succeeded. If the
    // token can't be stored, log it and carry on with a normal session.
    if ($remember) {
        try {
            $rm_token   = bin2hex(random_bytes(32));
            $rm_expires = time() + 30 * 24 * 3600;
            $pdo->prepare(
                'UPDATE ucp_accounts SET remember_token = ?, remember_expires = ? WHERE id = ?'
            )->execute([token_hash($rm_token), $rm_expires, $uid]);

            $secure = is_https();
            setcookie('bsucp_rm', $rm_token, [
                'expires'  => $rm_expires,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $secure,
            ]);
            // Extend the session cookie so it outlives the browser session.
            setcookie(session_name(), session_id(), [
                'expires'  => $rm_expires,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $secure,
            ]);
        } catch (Throwable $e) {
            error_log('UCP remember-me could not be stored: ' . $e->getMessage());
        }
    }

    // ---- "Last sign-in …" notice -------------------------------------------
    // Capture the PREVIOUS sign-in before overwriting it, and remember this
    // device so the login page can say "from this device" next time.
    $prev = $pdo->prepare('SELECT last_login, last_device FROM ucp_accounts WHERE id = ? LIMIT 1');
    $prev->execute([$uid]);
    $prevRow    = $prev->fetch();
    $prevLogin  = $prevRow['last_login'] ?? null;
    $prevDevice = $prevRow['last_device'] ?? null;

    $deviceRaw = $_COOKIE['bsucp_dev'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $deviceRaw)) {
        $deviceRaw = bin2hex(random_bytes(16));
    }
    $deviceHash = hash('sha256', $deviceRaw);
    $sameDevice = ($prevDevice !== null && hash_equals((string)$prevDevice, $deviceHash));

    $secureCookie = is_https();
    setcookie('bsucp_dev', $deviceRaw, [
        'expires'  => time() + 90 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secureCookie,
    ]);
    // Readable by the login page (not httponly) purely to render the notice.
    // Contains only a timestamp + a flag — no account identifiers.
    setcookie('bsucp_last', json_encode([
        'ts'   => time(),
        'same' => $sameDevice,
    ]), [
        'expires'  => time() + 90 * 24 * 3600,
        'path'     => '/',
        'httponly' => false,
        'samesite' => 'Lax',
        'secure'   => $secureCookie,
    ]);

    $pdo->prepare('UPDATE ucp_accounts SET last_login = NOW(), last_device = ? WHERE id = ?')
        ->execute([$deviceHash, $uid]);

    // ---- Lazy forum_member_id population ------------------------------------
    // If the user has logged into the forum via OAuth at least once, IPS will
    // have created their forum account. Look it up by email and store it now.
    $fmRow = $pdo->prepare('SELECT forum_member_id, email FROM ucp_accounts WHERE id = ? LIMIT 1');
    $fmRow->execute([$uid]);
    $fmData = $fmRow->fetch();

    if ($fmData && $fmData['forum_member_id'] === null) {
        require_once __DIR__ . '/_ips.php';
        $lookup = ips_endpoint('core/members', ['email' => $fmData['email']]);
        if ($lookup !== null) {
            $ch = curl_init($lookup);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                // Sign-in blocks on this, so keep it short — the lookup is
                // optional and a slow forum must not hold the user on a spinner.
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                // The API key rides in the query string, so following a
                // redirect would hand it to whatever host the redirect names.
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && $body) {
                $data = json_decode($body, true);
                if (isset($data['results'][0]['id'])) {
                    $pdo->prepare('UPDATE ucp_accounts SET forum_member_id = ? WHERE id = ?')
                        ->execute([$data['results'][0]['id'], $uid]);
                }
            }
        }
    }

    // If 2FA is mandatory for this rank and isn't on yet, the pages send them
    // to /security to set it up rather than into the UCP proper.
    $needsSetup = twofa_is_required($rank) && empty($acc['totp_enabled']);

    ok(array_merge([
        'id'   => $uid,                  // Account ID
        'name' => $acc['username'],
        'rank' => $rank,                 // 0–9
        'role' => rank_name($rank),      // display name ('' for Members)
        'redirect'    => $needsSetup ? '/security?setup=required' : '/dashboard',
        'last_login'  => $prevLogin,
        'same_device' => $sameDevice,
        'twofa'       => !empty($acc['totp_enabled']),
        'twofa_setup_required' => $needsSetup,
    ], $extra));
}
