# BlaineSide UCP — Backend Setup

This turns the front-end login/registration into a **working** system with
real accounts, unique Account IDs, and email verification.

Stack: **PHP 8.2 + MySQL 8.4** (both included in your OVH hosting).

---

## Overview of the flow

1. User registers → account saved as `pending`, gets a unique **Account ID**.
2. A verification email is sent from `noreply@blaineside.com`.
3. User clicks the link → account becomes `active`.
4. User logs in → session starts → dashboard loads their real name + Account ID.
5. Unverified users are refused at login until they confirm their email.

---

## STEP 1 — Create the database table

In OVH phpMyAdmin (Databases tab → `blainefucp` row → ··· → Go to phpMyAdmin),
open the **SQL** tab and run:

```sql
CREATE TABLE ucp_accounts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(20)  NOT NULL,
  username_lower VARCHAR(20) NOT NULL,
  email         VARCHAR(190) NOT NULL,
  email_lower   VARCHAR(190) NOT NULL,
  discord       VARCHAR(40)  DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  admin_rank    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  status        ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
  verify_token  VARCHAR(64)  DEFAULT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login    DATETIME     DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username_lower),
  UNIQUE KEY uq_email (email_lower)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000;
```

`id` is the **Account ID** — MySQL assigns each new account a unique number,
starting at 1000. These appear in URLs later (e.g. `?id=1042`).

`admin_rank` is the permission level (0–9). Everyone registers as **0 (Member)**.
A later admin panel will raise ranks. The numbers map to:

| Rank | Name |
|------|------|
| 0 | Member |
| 1 | Support Staff |
| 2 | Development Team |
| 3 | Trainee Admin |
| 4 | Admin Lvl 1 |
| 5 | Admin Lvl 2 |
| 6 | Senior Admin |
| 7 | Lead Admin |
| 8 | Management |
| 9 | Founder |

To promote someone by hand for now (until the admin panel exists), run e.g.:
```sql
UPDATE ucp_accounts SET admin_rank = 6 WHERE username_lower = 'dustin_hale';
```

---

## STEP 2 — Add PHPMailer (for sending email)

The email code uses PHPMailer (the standard library). Download it and place
three files in `api/lib/PHPMailer/`:

1. Go to https://github.com/PHPMailer/PHPMailer/releases (latest release).
2. From `src/`, copy these three files into `api/lib/PHPMailer/`:
   - `PHPMailer.php`
   - `SMTP.php`
   - `Exception.php`

Final structure:
```
api/lib/PHPMailer/PHPMailer.php
api/lib/PHPMailer/SMTP.php
api/lib/PHPMailer/Exception.php
```

(These are safe to commit to GitHub — they contain no secrets.)

---

## STEP 3 — Create config.php (DO NOT commit to GitHub)

1. Copy `api/config.example.php` → `api/config.php`.
2. Fill in:
   - **db.pass** — your `blainefucp` database password.
   - **smtp.pass** — your `noreply@blaineside.com` mailbox password.
3. Upload `config.php` to the server **via FTP only**. It is git-ignored so it
   never reaches GitHub. Your credentials stay off the public repo.

---

## STEP 4 — Deploy

Push everything EXCEPT `config.php` to GitHub → OVH auto-pulls. Then FTP the
single `config.php` into the `ucp/api/` folder on the server.

Final layout on the server (root = your `ucp` directory):
```
ucp/
  .htaccess            ← clean URLs, required
  index.html   login.html   create.html   verify.html
  reset.html   reset-confirm.html   welcome.html
  dashboard/
    index.html   bulletin.html
  assets/
    css/ucp.css   js/ucp.js   img/bg-sandy.jpg   fa/…
  docs/
  api/
    config.php          ← via FTP, NOT git
    _bootstrap.php  _mailer.php
    register.php  login.php  logout.php  session.php  verify.php  resend.php  check.php
    csrf.php  reset.php  reset-confirm.php
    oauth/
    .htaccess
    lib/PHPMailer/…
```

---

## STEP 5 — Test

1. Visit `ucp.blaineside.com` → register an account with a **real** email.
2. Check that inbox for the verification email → click the link.
3. Sign in. You should land on the dashboard greeting you by name.
4. In phpMyAdmin, the `ucp_accounts` table now has your row with its Account ID.

### If the email doesn't arrive
- Check spam/junk first.
- Confirm `noreply@blaineside.com` password is correct in `config.php`.
- OVH SMTP: host `ssl0.ovh.net`, port `465`, secure `ssl`. If 465 is blocked,
  try port `587` with secure `tls`.
- Use the "Resend email" button on the verify screen.

---

## Security notes

- Passwords are hashed with `password_hash()` (bcrypt) — never stored in plain text.
- All queries use prepared statements — safe from SQL injection.
- `config.php` is blocked from web access by `.htaccess` and kept out of git.
- Login uses a uniform "incorrect username or password" message so account
  existence isn't leaked.
- Basic per-session rate limiting on register/login/resend.
- **Change the database and mailbox passwords** now that they've been typed in
  chat, and update `config.php` to match.

---

## What's NOT done yet (future)

- Password reset via email (the front-end reset form exists; needs a
  `reset.php` + `reset-confirm.php` pair — say the word and I'll add them).
- Account ID pages (`?id=1042` lookups) — the schema supports it; the viewer
  page is a future build.
- Live dashboard data (stats, bulletins) still use mock arrays until wired.
