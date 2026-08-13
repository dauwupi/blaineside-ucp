# Two-factor authentication (TOTP)

Optional for everyone by default. A user turns it on at **/security**; from then
on their sign-in needs a 6-digit code from an authenticator app, or one of ten
single-use recovery codes.

Because the forum signs people in through the UCP's OAuth server, this covers
`forum.blaineside.com` too — `api/oauth/authorize.php` only ever proceeds when
`$_SESSION['uid']` is set, and that is not set until a code is accepted.

---

## Deploying

1. **Run `docs/migration-2fa.sql`** in phpMyAdmin. Do this *before or with* the
   file deploy — `api/login.php` selects the new columns and will error on every
   sign-in until they exist.
2. Push the files (git → FileZilla) as usual.
3. Optionally add the `security` block to `api/config.php` on the server — see
   `api/config.example.php`. Everything works without it.

Nothing changes for existing accounts: `totp_enabled` defaults to 0 and they
sign in exactly as before.

### Rolling back

Set `totp_enabled = 0` for everyone and 2FA is inert without touching any code:

```sql
UPDATE ucp_accounts SET totp_enabled = 0;
```

The columns and the extra endpoints can stay; nothing else reads them.

---

## Config (`api/config.php`)

```php
'security' => [
    'secret_key'         => '',    // see below
    'totp_required_rank' => null,  // null = optional for everyone
],
```

**`secret_key`** — encrypts stored TOTP secrets with AES-256-GCM, so a leaked
database dump yields no working second factors. Generate one with:

```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)), PHP_EOL;"
```

Back it up next to the database password. **Changing or losing it after people
have enabled 2FA locks them out** — their secrets become unreadable. The
security page detects this and tells them to contact a Founder rather than
blaming their phone. Recovery is manual: clear their 2FA (see below) and have
them set it up again.

**`totp_required_rank`** — set to `1` to make 2FA mandatory for all staff, `9`
for Founders only. Accounts at or above that rank are sent to `/security` on
sign-in until it's on, and the disable button is refused server-side. Members
(rank 0) are never affected unless you set it to `0`.

---

## Files

| File | Role |
|------|------|
| `api/lib/Totp.php` | RFC 6238 TOTP. Self-contained — no Composer. Verified against the RFC's test vectors. |
| `api/_2fa.php` | Secret encryption, recovery codes, the shared verify path, endpoint guards. |
| `api/_login_finish.php` | Everything a successful sign-in does. Shared by `login.php` and `2fa-verify.php` so the two can't drift. |
| `api/2fa-status.php` | GET — what the security page renders. |
| `api/2fa-setup.php` | POST `{password}` → secret + otpauth URI. Writes nothing to the account. |
| `api/2fa-confirm.php` | POST `{code}` → commits the secret, returns recovery codes. |
| `api/2fa-verify.php` | POST `{code}` → completes a half-finished sign-in. |
| `api/2fa-disable.php` | POST `{password, code}` → off, codes deleted. |
| `api/2fa-codes.php` | POST `{password, code}` → new recovery codes, old ones revoked. |
| `security.html` | The user-facing page, served at `/security`. |
| `assets/js/qrcode.js` | Vendored QR renderer (MIT). Local, not a CDN — the page draws a live secret. |

---

## How the sign-in splits

```
POST /api/login.php
      password wrong ─────────────► 401, lockout counter (probe '')
      password right, 2FA off ────► session established, done
      password right, 2FA on  ────► { requires_2fa: true }
                                    $_SESSION['pending_2fa'] = uid   (5 min TTL)
                                    'uid' is NOT set — nothing is signed in
POST /api/2fa-verify.php
      code wrong ─────────────────► 401, lockout counter (probe '2fa'),
                                    5 wrong codes burns the pending state
      code right ─────────────────► login_finish() — same session, same
                                    remember-me, same IPS lookup as a
                                    one-factor sign-in
```

`api/session.php` reports `pending_2fa: true` while half-authenticated, purely
so the login page can reopen the code prompt after a refresh. It still reports
`authenticated: false`, and every other endpoint treats the browser as signed
out.

---

## Design notes

**The secret isn't stored until a code proves the app has it.** `2fa-setup.php`
keeps it in the session only. Writing it up front is how people end up with 2FA
enabled against an app they never finished adding.

**Replay protection.** `totp_last_step` records the last accepted 30-second
step; anything at or below it is refused. Without it, a code read off someone's
screen stays usable for its whole window on any device.

**Enabling 2FA revokes every "remember me" device.** `_bootstrap.php` restores
a session straight from that token without ever reaching `login.php`, so a
laptop trusted before 2FA existed would keep walking straight in.

**Disabling needs both factors.** Password *and* a current code. Anything less
means a stolen session alone can strip the second factor off, which would make
the whole feature decorative.

**A password reset does not clear 2FA.** Inbox access alone must not be enough
to get past it. That is deliberate, and it is why the recovery codes matter.

**Recovery codes are shown once.** Ten codes, 12 characters each (~59 bits),
stored as `sha256('<uid>:<CODE>')`. Spent codes are marked `used_at`, not
deleted, so "they used a recovery code on the 3rd" stays answerable.

---

## Support: someone is locked out

If they've lost the phone **and** the recovery codes there is no self-service
route back in — that is the point. Verify who you're talking to, then:

```sql
UPDATE ucp_accounts
   SET totp_secret = NULL, totp_enabled = 0, totp_last_step = 0, totp_enabled_at = NULL
 WHERE username_lower = 'theirname';

DELETE FROM ucp_2fa_backup_codes
 WHERE uid = (SELECT id FROM ucp_accounts WHERE username_lower = 'theirname');
```

**"My codes are always wrong"** is nearly always the phone's clock. The verifier
tolerates ±30 seconds of drift; beyond that, tell them to set date and time to
automatic. If *everyone's* codes stop working at once, check `secret_key` in
`config.php` instead.
