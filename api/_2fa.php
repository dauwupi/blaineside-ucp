<?php
/**
 * BlaineSide UCP — two-factor helpers shared by every 2fa-*.php endpoint
 * and by login.php.
 *
 * Include AFTER _bootstrap.php (it uses db(), fail() and $CONFIG).
 *
 * Leading underscore keeps it unreachable over HTTP — both .htaccess files
 * deny ^_.*\.php$.
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/Totp.php';
require_once __DIR__ . '/_account.php';

/** How many recovery codes are issued at a time. */
const BS_BACKUP_CODE_COUNT = 10;

/** Characters used in recovery codes. I/L/O/U/0/1 are omitted — they are the
 *  pairs people mistype when copying a code off a printout. */
const BS_BACKUP_ALPHABET = 'ABCDEFGHJKMNPQRSTVWXYZ23456789';

/** Minutes a half-finished sign-in may sit at the code prompt. */
const BS_PENDING_TTL = 300;

/** Wrong codes allowed at the login prompt before the pending state is burned. */
const BS_2FA_MAX_TRIES = 5;


// ---------------------------------------------------------------------------
// Secret storage
//
// The TOTP secret is a bearer credential in the same sense as a password:
// anyone holding it can generate valid codes forever. Encrypting it at rest
// means a leaked database dump — the realistic threat for a community site —
// does not hand over everyone's second factor, because the key lives in
// config.php, which is gitignored and never in a dump.
//
// If no key is configured the secret is stored as plain base32 and everything
// still works; you just lose that one layer. Set `security.secret_key` in
// config.php to turn it on (see config.example.php).
// ---------------------------------------------------------------------------

/** The 32-byte encryption key from config, or null if none is configured. */
function twofa_key(): ?string
{
    global $CONFIG;
    $raw = (string)($CONFIG['security']['secret_key'] ?? '');
    if ($raw === '') return null;

    // Accept "base64:AAAA…" or a 64-char hex string.
    if (str_starts_with($raw, 'base64:')) {
        $key = base64_decode(substr($raw, 7), true);
    } elseif (preg_match('/^[a-f0-9]{64}$/i', $raw)) {
        $key = hex2bin($raw);
    } else {
        $key = null;
    }

    if ($key === false || $key === null || strlen($key) !== 32) {
        error_log('UCP 2FA: security.secret_key is not a valid 32-byte key — storing secrets unencrypted.');
        return null;
    }
    return $key;
}

/** Encrypts a base32 secret for the database. Returns it unchanged if no key. */
function twofa_encrypt_secret(string $secretB32): string
{
    $key = twofa_key();
    if ($key === null) return $secretB32;

    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($secretB32, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ct === false) {
        error_log('UCP 2FA: secret encryption failed — storing unencrypted.');
        return $secretB32;
    }
    return 'v1:' . base64_encode($iv . $tag . $ct);
}

/**
 * Reverses twofa_encrypt_secret(). Returns null if the stored value can't be
 * read — a changed or missing key — which the endpoints surface as "2FA is
 * misconfigured, contact staff" rather than "wrong code", so nobody spends
 * twenty minutes blaming their phone.
 */
function twofa_decrypt_secret(?string $stored): ?string
{
    if ($stored === null || $stored === '') return null;
    if (!str_starts_with($stored, 'v1:')) return $stored;   // legacy / unencrypted

    $key = twofa_key();
    if ($key === null) {
        error_log('UCP 2FA: encrypted secret found but no security.secret_key is set.');
        return null;
    }

    $blob = base64_decode(substr($stored, 3), true);
    if ($blob === false || strlen($blob) < 29) return null;

    $iv  = substr($blob, 0, 12);
    $tag = substr($blob, 12, 16);
    $ct  = substr($blob, 28);
    $out = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    return $out === false ? null : $out;
}


// ---------------------------------------------------------------------------
// Recovery codes
// ---------------------------------------------------------------------------

/** Strips formatting so "abcd-efgh-jkmn" and "ABCDEFGHJKMN" are the same code. */
function twofa_normalise_backup(string $code): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
}

/**
 * Stored hash for a recovery code.
 *
 * Plain SHA-256, salted with the account id. These are 12 characters from a
 * 30-symbol alphabet — about 59 bits of entropy from random_int, so there is
 * nothing to guess and no need for a slow KDF. The account id is mixed in so
 * an attacker with a dump can't test one candidate against every row at once.
 */
function twofa_backup_hash(int $uid, string $code): string
{
    return hash('sha256', $uid . ':' . twofa_normalise_backup($code));
}

/** One formatted recovery code, e.g. "K7M2-PQ4X-9TCF". */
function twofa_make_backup_code(): string
{
    $alphabet = BS_BACKUP_ALPHABET;
    $max      = strlen($alphabet) - 1;
    $out      = '';
    for ($i = 0; $i < 12; $i++) {
        if ($i > 0 && $i % 4 === 0) $out .= '-';
        $out .= $alphabet[random_int(0, $max)];
    }
    return $out;
}

/**
 * Replaces this account's recovery codes with a fresh set.
 *
 * Returns the plaintext codes — the ONLY time they exist in readable form.
 * The old set is deleted outright rather than marked used: they are being
 * revoked, not spent, and keeping them would leave live credentials in the
 * table.
 *
 * @return string[]
 */
function twofa_issue_backup_codes(PDO $pdo, int $uid): array
{
    $codes = [];
    for ($i = 0; $i < BS_BACKUP_CODE_COUNT; $i++) {
        $codes[] = twofa_make_backup_code();
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM ucp_2fa_backup_codes WHERE uid = ?')->execute([$uid]);
        $ins = $pdo->prepare(
            'INSERT INTO ucp_2fa_backup_codes (uid, code_hash, created_at) VALUES (?, ?, ?)'
        );
        foreach ($codes as $c) {
            $ins->execute([$uid, twofa_backup_hash($uid, $c), time()]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $codes;
}

/** How many unused recovery codes this account has left. */
function twofa_backup_remaining(PDO $pdo, int $uid): int
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM ucp_2fa_backup_codes WHERE uid = ? AND used_at IS NULL');
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}

/**
 * Spends a recovery code if it matches an unused one.
 *
 * Used codes are marked, not deleted, so "you used a recovery code" stays
 * visible in the table afterwards.
 */
function twofa_consume_backup_code(PDO $pdo, int $uid, string $code): bool
{
    $norm = twofa_normalise_backup($code);
    if (strlen($norm) !== 12) return false;

    // UPDATE … WHERE used_at IS NULL is the check and the claim in one
    // statement: two simultaneous requests with the same code produce one
    // affected row, so a code can't be spent twice by racing.
    $st = $pdo->prepare(
        'UPDATE ucp_2fa_backup_codes
            SET used_at = ?
          WHERE uid = ? AND code_hash = ? AND used_at IS NULL
          LIMIT 1'
    );
    $st->execute([time(), $uid, twofa_backup_hash($uid, $code)]);
    return $st->rowCount() === 1;
}


// ---------------------------------------------------------------------------
// Verification
// ---------------------------------------------------------------------------

/**
 * Checks a submitted second factor against an account row.
 *
 * $acc must contain id, totp_secret and totp_last_step.
 *
 * Returns 'totp' or 'backup' on success, or null. On a TOTP match the
 * matched time step is written to totp_last_step, which is what stops the
 * same six digits being replayed inside their validity window.
 */
function twofa_check(PDO $pdo, array $acc, string $code): ?string
{
    $uid    = (int)$acc['id'];
    $digits = preg_replace('/\D+/', '', $code) ?? '';

    // Six digits is a TOTP attempt; anything else can only be a recovery code.
    if (strlen($digits) === 6) {
        $secret = twofa_decrypt_secret($acc['totp_secret'] ?? null);
        if ($secret === null) return null;

        $step = Totp::verify($secret, $digits, 1, (int)($acc['totp_last_step'] ?? 0));
        if ($step !== null) {
            $pdo->prepare('UPDATE ucp_accounts SET totp_last_step = ? WHERE id = ?')
                ->execute([$step, $uid]);
            return 'totp';
        }
        return null;
    }

    return twofa_consume_backup_code($pdo, $uid, $code) ? 'backup' : null;
}


// ---------------------------------------------------------------------------
// Enforcement
//
// Off by default: 2FA is opt-in for everyone. Setting
// security.totp_required_rank in config.php to a rank (1 = Support Staff and
// above, 9 = Founder only) makes it mandatory from that rank up. Nothing else
// needs changing — session.php reports it and the pages act on it.
// ---------------------------------------------------------------------------

/** The lowest admin_rank that must have 2FA on, or null if nobody must. */
function twofa_required_rank(): ?int
{
    global $CONFIG;
    $r = $CONFIG['security']['totp_required_rank'] ?? null;
    if ($r === null || $r === '' || !is_numeric($r)) return null;
    $r = (int)$r;
    return ($r >= 0 && $r <= 9) ? $r : null;
}

/** Is this account required to have 2FA enabled? */
function twofa_is_required(int $rank): bool
{
    $min = twofa_required_rank();
    return $min !== null && $rank >= $min;
}

/** Name shown in the authenticator app. */
function twofa_issuer(): string
{
    global $CONFIG;
    return (string)($CONFIG['site']['name'] ?? 'BlaineSide UCP');
}


// ---------------------------------------------------------------------------
// Endpoint guards
// ---------------------------------------------------------------------------

/**
 * Both of these moved to _account.php when the profile page started needing
 * them too. Kept as wrappers so the 2fa-*.php endpoints read unchanged.
 */
function twofa_current_account(PDO $pdo): array
{
    return current_account($pdo);
}

function twofa_require_password(PDO $pdo, array $acc, string $password): void
{
    require_password($pdo, $acc, $password, '2fa_settings');
}
