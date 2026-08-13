<?php
/**
 * BlaineSide UCP — shared account guards for the signed-in endpoints.
 *
 * These were originally inside _2fa.php. Everything on the profile page needs
 * them, not just two-factor, so they live here and _2fa.php keeps its old
 * function names as thin wrappers — the 2fa-*.php endpoints are untouched.
 *
 * Include AFTER _bootstrap.php. Leading underscore keeps it unreachable over
 * HTTP (both .htaccess files deny ^_.*\.php$).
 */

declare(strict_types=1);

/**
 * Returns the signed-in account row, or ends the request with 401.
 *
 * Deliberately re-reads from the database rather than trusting $_SESSION: the
 * status and session_epoch checks are what make a suspension or a password
 * reset take effect on endpoints that change security settings — exactly the
 * ones where a stale session must not survive.
 */
function current_account(PDO $pdo): array
{
    if (empty($_SESSION['uid'])) {
        json_out(['ok' => false, 'authenticated' => false,
                  'error' => 'Please sign in first.'], 401);
    }

    $st = $pdo->prepare(
        'SELECT id, username, username_lower, email, email_lower, discord,
                password_hash, admin_rank, status, session_epoch, created_at,
                last_login, forum_member_id,
                name_changed_at, password_changed_at,
                pending_email, pending_email_expires,
                totp_enabled, totp_secret, totp_last_step
           FROM ucp_accounts WHERE id = ? LIMIT 1'
    );
    $st->execute([(int)$_SESSION['uid']]);
    $acc = $st->fetch();

    if (!$acc
        || $acc['status'] !== 'active'
        || (int)($_SESSION['epoch'] ?? 0) !== (int)$acc['session_epoch']) {
        $_SESSION = [];
        session_destroy();
        json_out(['ok' => false, 'authenticated' => false,
                  'error' => 'Your session has ended. Please sign in again.'], 401);
    }

    return $acc;
}

/**
 * Re-checks the account password before a setting is changed, and ends the
 * request with 401/429 if it doesn't match.
 *
 * Everything that changes how the account is reached asks for it. Without
 * this, a session left open on a shared machine — or one lifted via any XSS
 * the site ever grows — is enough to move the account to a new email and take
 * it over silently.
 *
 * $probe keys the lockout bucket so a run of wrong passwords here doesn't lock
 * the person out of signing in, and vice versa.
 */
function require_password(PDO $pdo, array $acc, string $password, string $probe = 'settings'): void
{
    $ip = client_ip();

    $lockLeft = lock_seconds_left($pdo, (int)$acc['id'], $ip, $probe);
    if ($lockLeft > 0) {
        json_out([
            'ok'         => false,
            'field'      => 'password',
            'error'      => 'Too many attempts. Try again shortly.',
            'locked'     => true,
            'locked_for' => $lockLeft,
        ], 429);
    }

    if ($password === '' || !password_verify($password, (string)$acc['password_hash'])) {
        $lockedFor = record_failure($pdo, (int)$acc['id'], $ip, $probe);
        json_out([
            'ok'         => false,
            'field'      => 'password',
            'error'      => 'That password is not correct.',
            'locked'     => $lockedFor > 0,
            'locked_for' => $lockedFor,
        ], $lockedFor > 0 ? 429 : 401);
    }

    clear_failures($pdo, (int)$acc['id'], $ip, $probe);
}

/**
 * Ends every OTHER session for this account and keeps the current one.
 *
 * session_epoch is the existing mechanism: session.php refuses any session
 * carrying an older value, so bumping it invalidates every cookie in
 * circulation. The caller's own session is then re-stamped with the new
 * value, which is what makes this "everywhere else" rather than "everywhere".
 *
 * remember_token is cleared in the same statement — a remembered device is
 * restored by _bootstrap.php without ever reaching login.php, so leaving it
 * would let the one device you most wanted to cut off walk straight back in.
 */
function sign_out_other_devices(PDO $pdo, int $uid): void
{
    $pdo->prepare(
        'UPDATE ucp_accounts
            SET session_epoch    = session_epoch + 1,
                remember_token   = NULL,
                remember_expires = NULL
          WHERE id = ?'
    )->execute([$uid]);

    $st = $pdo->prepare('SELECT session_epoch FROM ucp_accounts WHERE id = ? LIMIT 1');
    $st->execute([$uid]);
    $_SESSION['epoch']    = (int)$st->fetchColumn();
    $_SESSION['remember'] = false;

    setcookie('bsucp_rm', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => is_https(),
    ]);
}

/** Masks an address for display: kr•••••••••98@gmail.com */
function mask_email(string $email): string
{
    $at = strrpos($email, '@');
    if ($at === false || $at < 1) return '•••';

    $local  = substr($email, 0, $at);
    $domain = substr($email, $at);
    $len    = strlen($local);

    if ($len <= 2)  return str_repeat('•', $len) . $domain;
    if ($len <= 4)  return $local[0] . str_repeat('•', $len - 1) . $domain;

    return substr($local, 0, 2) . str_repeat('•', $len - 4) . substr($local, -2) . $domain;
}

/**
 * Names nobody may claim. Kept identical to api/check.php — the live
 * availability check on the form and the check on submit must agree, or the
 * form goes green on a name the server then refuses.
 */
const BS_RESERVED_NAMES = [
    'admin','administrator','owner','blaineside','staff','moderator',
    'support','root','system','noreply',
];

/** Days a player must wait between UCP name changes. */
const BS_NAME_CHANGE_DAYS = 30;
