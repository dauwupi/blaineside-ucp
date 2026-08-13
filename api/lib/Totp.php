<?php
/**
 * BlaineSide UCP — TOTP (RFC 6238) implementation.
 *
 * Deliberately self-contained: this project has no Composer autoloader and
 * vendors its dependencies by hand (see api/lib/PHPMailer), so pulling in
 * robthree/twofactorauth would mean introducing a whole package manager to
 * the deploy for ~150 lines of arithmetic. Everything here is standard
 * library — hash_hmac, pack, random_bytes — and is verified against the
 * RFC 6238 test vectors.
 *
 * Compatible with Google Authenticator, Authy, 1Password, Aegis, Ente Auth
 * and anything else that speaks otpauth:// — SHA-1, 6 digits, 30s period.
 * Those three values are what the authenticator apps assume when the URI
 * omits them, so they are not configurable: changing one silently breaks
 * every app that ignores the extra parameters.
 */

declare(strict_types=1);

final class Totp
{
    /** Seconds per code. */
    public const PERIOD = 30;

    /** Digits shown to the user. */
    public const DIGITS = 6;

    /** HMAC algorithm. SHA-1 here is the RFC default and is not a weakness:
     *  it is used as a MAC over a counter, not as a collision-resistant hash. */
    private const ALGO = 'sha1';

    /** RFC 4648 base32 alphabet — the encoding every authenticator expects. */
    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * A fresh shared secret, base32-encoded and unpadded.
     *
     * 20 bytes (160 bits) matches the RFC's recommended SHA-1 key length and
     * produces a 32-character secret — short enough that someone can type it
     * in by hand when their camera won't cooperate with the QR code.
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * Verify a user-supplied code.
     *
     * Returns the matched time step on success, or null on failure. The step
     * is the caller's replay defence: store it and pass it back as
     * $afterStep next time so the same code can never be accepted twice.
     * Without that, a code shoulder-surfed (or read off a phone left
     * unlocked) stays usable for its full 30-second life plus the window.
     *
     * @param string   $secretB32 Base32 secret as stored for the account.
     * @param string   $code      Whatever the user typed. Spaces are ignored.
     * @param int      $window    Steps of clock drift tolerated either side.
     * @param int      $afterStep Reject any step <= this. 0 disables the check.
     * @param int|null $now       Unix time; injectable for the test vectors.
     */
    public static function verify(
        string $secretB32,
        string $code,
        int $window = 1,
        int $afterStep = 0,
        ?int $now = null
    ): ?int {
        $code = preg_replace('/\D+/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) return null;

        $key = self::base32Decode($secretB32);
        if ($key === null || $key === '') return null;

        $current = intdiv($now ?? time(), self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            $step = $current + $i;
            if ($step <= $afterStep) continue;          // already used, or older
            // hash_equals, not ===: string comparison short-circuits on the
            // first differing byte, and the timing of that leaks how much of
            // a guess was correct.
            if (hash_equals(self::codeAt($key, $step), $code)) {
                return $step;
            }
        }
        return null;
    }

    /** The 6-digit code for a given time step. Raw (already decoded) key. */
    public static function codeAt(string $key, int $step): string
    {
        // 8-byte big-endian counter, per RFC 4226.
        $hash = hash_hmac(self::ALGO, pack('J', $step), $key, true);

        // Dynamic truncation: the low nibble of the last byte picks the offset.
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $binary = ((ord($hash[$offset])     & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                |  (ord($hash[$offset + 3]) & 0xFF);

        return str_pad(
            (string)($binary % (10 ** self::DIGITS)),
            self::DIGITS,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * The otpauth:// URI an authenticator app scans.
     *
     * The label is "Issuer:account" and the issuer is ALSO repeated as a
     * parameter — old versions of Google Authenticator read one, newer ones
     * read the other, and getting it wrong shows the entry as a bare
     * username with no idea which site it belongs to.
     */
    public static function uri(string $secretB32, string $account, string $issuer): string
    {
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);
        return 'otpauth://totp/' . $label . '?' . http_build_query([
            'secret'    => $secretB32,
            'issuer'    => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Groups a secret into blocks of four for manual entry. */
    public static function pretty(string $secretB32): string
    {
        return trim(chunk_split($secretB32, 4, ' '));
    }

    // ---- base32 (RFC 4648, no padding) -------------------------------------

    public static function base32Encode(string $raw): string
    {
        $bits = '';
        for ($i = 0, $n = strlen($raw); $i < $n; $i++) {
            $bits .= str_pad(decbin(ord($raw[$i])), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::B32[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }
        return $out;
    }

    /** Returns null for anything that isn't valid base32. */
    public static function base32Decode(string $b32): ?string
    {
        $b32 = strtoupper(str_replace([' ', '-', '='], '', $b32));
        if ($b32 === '' || strspn($b32, self::B32) !== strlen($b32)) return null;

        $bits = '';
        for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
            $bits .= str_pad(decbin(strpos(self::B32, $b32[$i])), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        // Trailing bits that don't complete a byte are padding — drop them.
        foreach (str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $byte) {
            $out .= chr(bindec($byte));
        }
        return $out;
    }
}
