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


## Live links wired

- Login "Back to site" and the wordmark → https://forum.blaineside.com
- Discord invite (login "Ask in our Discord" + dashboard "Join the Discord") → https://discord.gg/8GUuTBcEsD
- Login background video → `bg-video-720.mp4` (autoplay, muted, looped)

## Still needs the backend

- Dashboard Discord **stats** now pull live from Discord's public invite +
  widget API (`loadDiscordStats()` in dashboard.html). To get the live
  "online now" count, enable the widget: Discord → Server Settings → Widget →
  Enable Server Widget, then set `DISCORD_GUILD_ID` in dashboard.html. The
  invite-based member/online counts work without it. Hardcoded numbers remain
  as a fallback if Discord can't be reached.
- The signed-in **UCP name** drives the greeting ("Welcome back, …") and the
  top-right account block via `UCP_NAME` / `UCP_ROLE` in dashboard.html. The
  demo passes it from login via `?u=`; the backend should set it from the
  real session instead. Players (no staff rank) → set `UCP_ROLE=''`.
- Account creation, login, email verification, password reset, and
  username/email availability are all mocked (see notes above).
