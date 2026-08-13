<?php
/**
 * BlaineSide UCP — session tracking and the security activity log.
 *
 * Two things live here because they are written from the same places:
 *
 *   ucp_sessions      one row per signed-in browser, so "Where you're signed
 *                     in" can list devices and end them individually.
 *   ucp_security_log  append-only history of sign-ins and of every change to
 *                     how the account is reached.
 *
 * Everything in here is defensive. A device list is a nicety; a sign-in is
 * not. If either table is missing or the database hiccups, every function
 * fails quietly and the UCP carries on — the page then shows an empty state
 * rather than an error, and nobody is locked out of their account because a
 * logging table went away.
 *
 * Include AFTER _bootstrap.php. The leading underscore keeps it unreachable
 * over HTTP (both .htaccess files deny ^_.*\.php$).
 */

declare(strict_types=1);

/** Sessions untouched for this long are pruned at the next sign-in. */
const BS_SESSION_IDLE_DAYS = 30;

/** Log entries older than this are pruned at the next sign-in. */
const BS_SECLOG_KEEP_DAYS = 90;

/** How often a live session re-writes last_seen. Every request would be one
 *  UPDATE per asset load for no extra information. */
const BS_SESSION_TOUCH_SECS = 60;


/* =====================================================================
   Presentation helpers
   ===================================================================== */

/**
 * "Chrome on Windows" from a user-agent string.
 *
 * Deliberately coarse. The point is for someone to recognise their own
 * devices in a list, not to fingerprint them — and a wrong-but-confident
 * version number is worse than no version number.
 */
function bs_device_label(string $ua): string
{
    if ($ua === '') return 'Unknown device';

    $browser = 'Browser';
    // Order matters: Edge and Opera both claim to be Chrome, Chrome claims
    // to be Safari. Most specific first.
    if (preg_match('/Edg[A-Z]?\//i', $ua))              $browser = 'Edge';
    elseif (preg_match('/OPR\/|Opera/i', $ua))          $browser = 'Opera';
    elseif (preg_match('/SamsungBrowser/i', $ua))       $browser = 'Samsung Internet';
    elseif (preg_match('/Vivaldi/i', $ua))              $browser = 'Vivaldi';
    elseif (preg_match('/Brave/i', $ua))                $browser = 'Brave';
    elseif (preg_match('/Firefox|FxiOS/i', $ua))        $browser = 'Firefox';
    elseif (preg_match('/Chrome|CriOS|Chromium/i', $ua))$browser = 'Chrome';
    elseif (preg_match('/Safari/i', $ua))               $browser = 'Safari';

    $os = 'an unknown system';
    if (preg_match('/Windows NT/i', $ua))               $os = 'Windows';
    elseif (preg_match('/iPhone/i', $ua))               $os = 'iPhone';
    elseif (preg_match('/iPad/i', $ua))                 $os = 'iPad';
    elseif (preg_match('/Android/i', $ua))              $os = 'Android';
    elseif (preg_match('/CrOS/i', $ua))                 $os = 'ChromeOS';
    elseif (preg_match('/Mac OS X|Macintosh/i', $ua))   $os = 'macOS';
    elseif (preg_match('/Linux/i', $ua))                $os = 'Linux';

    return $browser . ' on ' . $os;
}

/** Rough shape of the device, so the page can pick an icon. */
function bs_device_kind(string $ua): string
{
    if (preg_match('/iPhone|Android.*Mobile|Windows Phone/i', $ua)) return 'phone';
    if (preg_match('/iPad|Tablet|Android/i', $ua))                  return 'tablet';
    return 'desktop';
}

/**
 * 86.13.•.• — enough to recognise your own network, not enough to be worth
 * copying out of a screenshot. Staff read the full value from the database.
 */
function bs_mask_ip(?string $ip): string
{
    $ip = (string)$ip;
    if ($ip === '') return '—';

    if (strpos($ip, ':') !== false) {                 // IPv6
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 2)) . ':•';
    }
    $parts = explode('.', $ip);
    if (count($parts) !== 4) return '•';
    return $parts[0] . '.' . $parts[1] . '.•.•';
}


/* =====================================================================
   Sessions
   ===================================================================== */

/**
 * Records the browser that has just signed in, and returns its session id.
 *
 * The id is ours, not PHP's. session_regenerate_id() runs at sign-in and
 * again on every password change; keying the row on the PHP session id would
 * orphan the row each time and leave the list full of ghosts.
 */
function session_begin(PDO $pdo, int $uid, bool $remember): void
{
    try {
        $sid = bin2hex(random_bytes(16));
        $ua  = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $pdo->prepare(
            'INSERT INTO ucp_sessions
                (id, account_id, device, user_agent, ip, remembered, created_at, last_seen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $sid, $uid, bs_device_label($ua), $ua, client_ip(),
            $remember ? 1 : 0, time(), time(),
        ]);

        $_SESSION['sid']        = $sid;
        $_SESSION['sid_touch']  = time();
        // logout.php is standalone and can't call bs_device_label(), so it
        // reads the label from here.
        $_SESSION['sid_device'] = bs_device_label($ua);

        sessions_prune($pdo);
    } catch (Throwable $e) {
        error_log('UCP session_begin: ' . $e->getMessage());
    }
}

/**
 * Keeps last_seen current and reports whether this session is still allowed.
 *
 * Returns FALSE only when we positively know the session was revoked — the
 * caller then ends it. Every other outcome (no table, database down, no row
 * yet) returns TRUE: a logging feature must never be able to sign the whole
 * site out.
 */
function session_touch(PDO $pdo, int $uid): bool
{
    try {
        $sid = $_SESSION['sid'] ?? null;

        // Sessions that predate this feature, and remember-me restores, have
        // no row yet. Adopt them rather than leaving them invisible.
        if (!$sid) {
            session_begin($pdo, $uid, !empty($_SESSION['remember']));
            return true;
        }

        $st = $pdo->prepare('SELECT account_id, revoked_at FROM ucp_sessions WHERE id = ? LIMIT 1');
        $st->execute([$sid]);
        $row = $st->fetch();

        if (!$row) {                       // pruned out from under us
            session_begin($pdo, $uid, !empty($_SESSION['remember']));
            return true;
        }
        if ((int)$row['account_id'] !== $uid) return true;   // not ours to judge
        if ($row['revoked_at'] !== null)      return false;  // signed out elsewhere

        if (time() - (int)($_SESSION['sid_touch'] ?? 0) >= BS_SESSION_TOUCH_SECS) {
            $pdo->prepare('UPDATE ucp_sessions SET last_seen = ?, ip = ? WHERE id = ?')
                ->execute([time(), client_ip(), $sid]);
            $_SESSION['sid_touch'] = time();
        }
        return true;
    } catch (Throwable $e) {
        error_log('UCP session_touch: ' . $e->getMessage());
        return true;
    }
}

/** Marks the current session as ended — used by logout. */
function session_end(PDO $pdo): void
{
    try {
        if (!empty($_SESSION['sid'])) {
            $pdo->prepare('UPDATE ucp_sessions SET revoked_at = ? WHERE id = ? AND revoked_at IS NULL')
                ->execute([time(), $_SESSION['sid']]);
        }
    } catch (Throwable $e) {
        error_log('UCP session_end: ' . $e->getMessage());
    }
}

/** Ends every session for this account except the one making the request. */
function sessions_revoke_others(PDO $pdo, int $uid): void
{
    try {
        $pdo->prepare(
            'UPDATE ucp_sessions SET revoked_at = ?
              WHERE account_id = ? AND revoked_at IS NULL AND id <> ?'
        )->execute([time(), $uid, (string)($_SESSION['sid'] ?? '')]);
    } catch (Throwable $e) {
        error_log('UCP sessions_revoke_others: ' . $e->getMessage());
    }
}

/**
 * Ends one named session. Returns its device label, or null if the id
 * doesn't belong to this account — which is also the answer given when it
 * simply doesn't exist, so nobody can probe for other people's session ids.
 */
function session_revoke_one(PDO $pdo, int $uid, string $sid): ?string
{
    try {
        $st = $pdo->prepare('SELECT device, remembered FROM ucp_sessions WHERE id = ? AND account_id = ? LIMIT 1');
        $st->execute([$sid, $uid]);
        $row = $st->fetch();
        if (!$row) return null;

        $pdo->prepare('UPDATE ucp_sessions SET revoked_at = ? WHERE id = ? AND revoked_at IS NULL')
            ->execute([time(), $sid]);

        // A remembered device can walk straight back in without ever reaching
        // login.php, so revoking its session is only half the job. There is one
        // remember token per account, so clearing it costs the other remembered
        // devices a fresh sign-in — the right trade when someone has just said
        // "that device isn't mine".
        if (!empty($row['remembered'])) {
            $pdo->prepare(
                'UPDATE ucp_accounts SET remember_token = NULL, remember_expires = NULL WHERE id = ?'
            )->execute([$uid]);
        }

        return (string)($row['device'] ?? 'a device');
    } catch (Throwable $e) {
        error_log('UCP session_revoke_one: ' . $e->getMessage());
        return null;
    }
}

/**
 * The live sessions for this account, current one first.
 *
 * A revoked session is dropped from the list the moment it is revoked, not
 * when its browser next notices — the person who just pressed the button
 * should see it gone.
 */
function sessions_list(PDO $pdo, int $uid): array
{
    try {
        $st = $pdo->prepare(
            'SELECT id, device, user_agent, ip, remembered, created_at, last_seen
               FROM ucp_sessions
              WHERE account_id = ? AND revoked_at IS NULL
                AND last_seen > ?
              ORDER BY last_seen DESC
              LIMIT 20'
        );
        $st->execute([$uid, time() - BS_SESSION_IDLE_DAYS * 86400]);

        $here = (string)($_SESSION['sid'] ?? '');
        $out  = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'id'      => $r['id'],
                'current' => $r['id'] === $here,
                'device'  => $r['device'] ?: 'Unknown device',
                'kind'    => bs_device_kind((string)$r['user_agent']),
                'ip'      => bs_mask_ip($r['ip']),
                'remembered' => (bool)$r['remembered'],
                'created_at' => (int)$r['created_at'],
                'last_seen'  => (int)$r['last_seen'],
            ];
        }
        // The session you are reading this on belongs at the top.
        usort($out, function ($a, $b) {
            if ($a['current'] !== $b['current']) return $a['current'] ? -1 : 1;
            return $b['last_seen'] <=> $a['last_seen'];
        });
        return $out;
    } catch (Throwable $e) {
        error_log('UCP sessions_list: ' . $e->getMessage());
        return [];
    }
}

/** Drops long-dead rows. Called at sign-in, which is rare enough to be free. */
function sessions_prune(PDO $pdo): void
{
    try {
        $pdo->prepare('DELETE FROM ucp_sessions WHERE last_seen < ?')
            ->execute([time() - BS_SESSION_IDLE_DAYS * 86400]);
        $pdo->prepare('DELETE FROM ucp_security_log WHERE created_at < ?')
            ->execute([time() - BS_SECLOG_KEEP_DAYS * 86400]);
    } catch (Throwable $e) {
        error_log('UCP prune: ' . $e->getMessage());
    }
}


/* =====================================================================
   Security activity log
   ===================================================================== */

/**
 * Writes one line of history.
 *
 * $detail is shown to the person as-is, so it must never carry anything they
 * shouldn't see in a screenshot — no tokens, no full addresses, no codes.
 */
function security_log(PDO $pdo, int $uid, string $event, string $detail = '', string $level = 'info'): void
{
    try {
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $pdo->prepare(
            'INSERT INTO ucp_security_log
                (account_id, event, detail, level, device, ip, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $uid, substr($event, 0, 40), ($detail === '' ? null : substr($detail, 0, 190)),
            in_array($level, ['info','good','warn'], true) ? $level : 'info',
            bs_device_label($ua), client_ip(), time(),
        ]);
    } catch (Throwable $e) {
        error_log('UCP security_log: ' . $e->getMessage());
    }
}

/** The most recent entries, newest first. */
function security_log_list(PDO $pdo, int $uid, int $limit = 25): array
{
    try {
        $limit = max(1, min(100, $limit));
        $st = $pdo->prepare(
            "SELECT event, detail, level, device, ip, created_at
               FROM ucp_security_log
              WHERE account_id = ?
              ORDER BY created_at DESC, id DESC
              LIMIT $limit"
        );
        $st->execute([$uid]);

        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = [
                'event'   => $r['event'],
                'detail'  => $r['detail'],
                'level'   => $r['level'],
                'device'  => $r['device'] ?: 'Unknown device',
                'ip'      => bs_mask_ip($r['ip']),
                'at'      => (int)$r['created_at'],
            ];
        }
        return $out;
    } catch (Throwable $e) {
        error_log('UCP security_log_list: ' . $e->getMessage());
        return [];
    }
}

/**
 * Whether the two tables exist.
 *
 * api/profile.php reports this to the page as a feature flag, so a UCP where
 * the migration hasn't been run yet shows the honest "not available" state
 * instead of an empty list that looks like "you have never signed in".
 */
function sessions_available(PDO $pdo): bool
{
    static $known = null;
    if ($known !== null) return $known;
    try {
        $pdo->query('SELECT 1 FROM ucp_sessions LIMIT 1');
        $pdo->query('SELECT 1 FROM ucp_security_log LIMIT 1');
        $known = true;
    } catch (Throwable $e) {
        $known = false;
    }
    return $known;
}
