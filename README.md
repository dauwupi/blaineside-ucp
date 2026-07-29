# BlaineSide UCP

User Control Panel for BlaineSide — sign-in, registration, email verification,
password reset, and the member dashboard. Static pages on Apache with a small
PHP/MySQL API. No build step.

## Flow

```
/ ─▶ /login ─▶ /dashboard
        │
        ├─ /create ─▶ /verify ─▶ (email link) ─▶ /welcome
        └─ /reset  ─▶ (email link) ─▶ /reset-confirm
```

Sign-in also serves the forum: `forum.blaineside.com` sends players here via
OAuth, and `login.html` hands them back afterwards.

## Layout

| Path | What it holds |
|------|---------------|
| `.htaccess` | Clean-URL rules. Serves every page without `.html` and 301s the old `.html` paths to the new ones. |
| `*.html` (root) | The auth pages, served at `/login`, `/create`, `/verify`, `/reset`, `/reset-confirm`, `/welcome`. **The files must keep these names** — the rewrite rules, the forum's OAuth hand-off and links in already-sent emails all depend on them. |
| `dashboard/index.html` | Main UCP dashboard — served at `/dashboard` |
| `dashboard/*.html` | Sub-pages — `bulletin.html` is served at `/dashboard/bulletin` |
| `assets/css/` | `ucp.css` — shared tokens, backdrop, header/footer |
| `assets/js/` | `ucp.js` — API access, CSRF, time-of-day backdrop, clock |
| `assets/img/` | `bg-sandy.jpg` — the Sandy Shores backdrop |
| `assets/fa/` | FontAwesome 4.7 stylesheet + webfont |
| `api/` | PHP endpoints (see below) |
| `api/oauth/` | OAuth provider for the forum — **paths registered inside IPS** |
| `api/lib/` | PHPMailer |
| `docs/` | Setup guide, integration notes, SQL migration |

## API

| Endpoint | Purpose |
|----------|---------|
| `csrf.php` | Issues the session's CSRF token |
| `login.php` | Authenticates; enforces the lockout; sets remember-me |
| `logout.php` | Ends the session |
| `register.php` | Creates a pending account, emails the verification link |
| `verify.php` | Consumes the email token, activates, redirects to `welcome.html` |
| `resend.php` | Re-sends the verification email |
| `reset.php` | Emails a password-reset link (30-minute, single-use token) |
| `reset-confirm.php` | Sets the new password, invalidates all sessions |
| `check.php` | Live username/email availability for the register form |
| `session.php` | Returns the signed-in user (dashboard calls this on load) |

All state-changing endpoints require the CSRF token, sent as `X-CSRF-Token`.
Passwords are bcrypt via `password_hash()`; every query is a prepared statement.

## Setup

`api/config.php` holds the database and SMTP credentials. It is git-ignored and
uploaded by FTP only — never committed. Copy `api/config.example.php` to start.

See `docs/SETUP.md` for first-time setup and `docs/migration.sql` for the schema.
