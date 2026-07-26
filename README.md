# BlaineSide UCP

Front-end for the BlaineSide User Control Panel (design/prototype phase).
Self-contained HTML files — no build step, no dependencies.

## Flow

`index.html` → `login.html` (front door) → `dashboard.html` → `bulletin.html`

## Files

| File | Purpose |
|------|---------|
| `index.html` | Entry point — redirects to the login page |
| `login.html` | Sign in / Create UCP / verify email / reset password |
| `dashboard.html` | Main UCP dashboard |
| `bulletin.html` | County Bulletin management page |

## Demo credentials (front-end only)

- Username: `Name101`
- Password: `BlaineCounty26`

Signing in with these redirects to `dashboard.html`. These are placeholders for
the design demo — real authentication is handled by the backend.

## IMPORTANT: accounts need a backend

`login.html` validates input, checks password strength, detects caps lock, and
handles lockout — but it CANNOT actually create or verify accounts on its own.
Creating a UCP account requires a server to store users, hash passwords, send
verification emails, and issue sessions. The submit functions in `login.html`
are mocked with clear comments marking where the backend developer hooks in:

- `submitLogin()` — replace the demo check with a real auth request; on success
  it redirects to `dashboard.html`
- `submitRegister()` — POST the new account to the server; the verify screen is
  already built
- `submitReset()` — trigger a real password-reset email
- Availability checks (`onUserInput`, `onEmailInput`) — replace the mocked
  TAKEN_NAMES / USED_EMAILS lists with real lookups

## Local preview

```bash
python3 -m http.server 8000
# visit http://localhost:8000
```
