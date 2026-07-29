# BlaineSide UCP — Integration & Wiring Guide

These six pages are **design mockups**: the layout, styling, copy, and all the *interactive UX* (validation states, strength meter, lockout countdown, loading transition, time-of-day background) are finished and self-contained. What is **not** real yet is anything that needs a database or server: account lookups, password checks, sending email, and the live player count. This guide is the checklist for wiring them into `ucp.blaineside.com`.

> Rule of thumb: **every red/green result, count, and "taken/exists" message in these pages is faked in the browser for the demo.** Treat all of it as UX only. The real decisions must happen on the server (your `api/`), and the page just reflects the answer.

---

## 1. The files & how they map to real routes

| Bundle file | What it is | Suggested real route |
|---|---|---|
| `BlaineSide-ucp-login.html` | Sign in + landing | `login.html` (your current one — replace it) |
| `BlaineSide-ucp-create.html` | Register a UCP | `create.html` |
| `BlaineSide-ucp-verify.html` | "Check your email" (shown after register) | `verify.html` |
| `BlaineSide-ucp-reset.html` | Request a password-reset link | `reset.html` |
| `BlaineSide-ucp-newpassword.html` | Set a new password (opened from the reset email) | `reset-confirm.html` |
| `BlaineSide-ucp-verified.html` | "You're in — Welcome" (opened from the verify email) | `welcome.html` |

The pages currently link to each other by their **bundle filenames** (`BlaineSide-ucp-*.html`) so they work when opened together. When you deploy, do a find-and-replace of those hrefs to your real route names.

### Cross-links to re-point
- **login** → create (the "Create UCP" tab **and** "Create a UCP" link), reset ("Forgot password?"), Discord (`discord.gg/8GUuTBcEsD`), nav → `blaineside.com` + `forum.blaineside.com`
- **create** → login (Sign in tab + "Already have an account"), and the **Create UCP** button currently just navigates to `verify` — in production that button submits the form; only go to `verify` on a successful create.
- **verify** → login, → create ("Entered the wrong address? Change it")
- **reset** → login; the emailed link (not an on-page link) is what opens **newpassword**
- **newpassword** → login (on success)
- **verified** → login ("Sign in to your UCP")

---

## 2. Must move to the server (security-critical)

These are demo-only in the page and are trivially bypassable as-is. Re-implement them in `api/`:

**a) Login existence + password.** The page checks a hardcoded `ACCOUNTS` object. Replace with a POST to your auth endpoint. The endpoint verifies the password against your hash (bcrypt/argon2) and returns a result the page renders.
- ⚠️ **Username enumeration:** the page shows *"No UCP exists with the name X."* That's friendly but tells attackers which names are real. For a public login, the safer message is a **single generic** *"Incorrect UCP name or password."* for both wrong-name and wrong-password. Your reset page already uses the correct non-revealing wording ("if that address belongs to a UCP…") — keep that. Decide per your threat model; if you keep the specific message, at least rate-limit hard.

**b) The 3-attempt lockout.** The countdown in the page is UX only — a refresh clears it. **Enforce it server-side**, keyed by *account + IP*, storing `attempt_count` and `locked_until` (DB or Redis). The escalation (30s → 5min → 15min) must be tracked server-side too; the page just displays whatever `locked_until` the server returns.

**c) Registration availability.** The "name/email already taken" check uses hardcoded lists. Wire the fields to a debounced availability endpoint (e.g. `GET /api/ucp/available?name=…`) for the live green/red, **and re-validate on submit** server-side (the client check is only a hint).

**d) Password rules (8+ / uppercase / number).** Enforce the exact same rules server-side on both register and reset. Keep the two in sync. Consider adding a max length and a breached-password check (HaveIBeenPwned k-anonymity) later.

**e) Bot check (you said "later").** When ready, drop Cloudflare Turnstile or hCaptcha into the **create** and **reset** forms and verify the token server-side.

**f) CSRF.** Add a CSRF token to every POST form (login, create, reset, newpassword). Add proper `<form method="post">` wrappers and `name`/`autocomplete` attributes (`autocomplete="username"`, `current-password`, `new-password`) so password managers and progressive enhancement work.

---

## 3. Dynamic data to inject

**Server status (login).** The count is data-driven — you only change two attributes and the JS recomputes the number, percentage, bar width, and colour:
```html
<div class="live" id="live" data-count="1412" data-max="2048"> … </div>
```
- Colour thresholds are already wired: **green < 60%, amber 60–85%, red > 85%.**
- You don't have a server yet, so leave the static values. When you do, have your template write the real `data-count`/`data-max` (or fetch `players.json`/`info.json` from the FiveM/txAdmin endpoint and set them). Nothing else needs to change.

**Footer clock ("UTC 09:13").** Static placeholder — make it live with a tiny JS `setInterval`, or remove it.

**"Remember me".** Wire to session lifetime, and to the last-sign-in cookie below.

---

## 4. The "Last sign-in 2 days ago · from this device" notice — when to show it

This is currently **hardcoded**, which is the one thing you must not ship as-is (a fake "2 days ago" for a first-time visitor looks broken). Correct behaviour:

**Show it only when you actually have a real value.** Otherwise remove the element. Recommended logic:

1. On a **successful login**, set a persistent (e.g. 90-day) cookie on that device, e.g. `bs_last = {ucp_id, ts, device_token}`. Also store `last_login_at` + a hashed device token on the account server-side.
2. On the **next visit to the login page**, read the cookie:
   - **Cookie present** → show the notice, populated from the real timestamp as a *relative* time ("3 hours ago", "yesterday", "2 days ago"). Compare the device token: if it matches, say **"· from this device"**; if the account is being accessed from an unrecognised device, either omit that clause or say **"· new device"**.
   - **No cookie** (first visit / cleared / private window) → **hide the element entirely.** Don't guess.
3. Never derive it from the username field before authentication — looking up a stranger's last-login as they type leaks account existence and timing. Cookie-based (their own device remembering their own last login) avoids that.

In short: **real value → show with relative time; no real value → delete the `<div class="signnote">`.**

---

## 5. Email flows & tokens

**Register → verify → welcome:**
1. `create` POST → create the account as **unverified** → generate a single-use, expiring, signed token → email a link like `…/verify-confirm?token=…` → render the **verify** ("check your email") page.
2. User clicks the email link → server validates + consumes the token, marks verified → render the **verified/welcome** page → user signs in.

**Reset → newpassword:**
1. `reset` POST → **always** render the neutral "sent" state regardless of whether the email exists (already built that way — don't change it).
2. If the email exists, send a link like `…/reset-confirm?token=…` (single-use, **30-min expiry** — the copy already says 30 minutes).
3. Link opens **newpassword** with the token; on submit, POST `{token, new_password}`; server validates the token + password rules, updates the hash, invalidates the token and all existing sessions → success state.

**Token hygiene:** random or signed, single-use, expiring, invalidated after use or password change.

**Deliverability:** the pages tell users the mail comes from **noreply@blaineside.com** and to check spam — so set up **SPF, DKIM, and DMARC** for that sender or the links will land in spam.

---

## 6. Time-of-day background

The tint over the photo changes by time bucket and always stays dark: **night / dawn / day / dusk**. It's set on load:
```js
var h = new Date().getUTCHours();
// h in 5–7 = dawn, 8–16 = day, 17–20 = dusk, else night
stage.classList.add('tod-' + bucket);
```
Note: `getUTCHours()` uses the **visitor's** clock converted to UTC — correct as long as their device clock is right. You said UTC = server time; if you want it locked to the **server's** clock regardless of the visitor, print the server hour into the page and use that instead:
```html
<body data-server-hour="{{ gmdate('G') }}">   <!-- PHP example: 0–23 UTC -->
```
```js
var h = +document.body.getAttribute('data-server-hour');
```
Bucket boundaries and the four tint colours are plain CSS (`.stage.tod-*`) — tune freely.

---

## 7. Production optimisation (recommended, not required)

Each page currently **inlines** the FontAwesome font and the Sandy Shores background as base64, so every page is ~270 KB and repeats the same assets. For a real site:
- Extract the background to one file, e.g. `/assets/bg-sandy.jpg`, and set `.scene { background-image:url(/assets/bg-sandy.jpg) }` instead of the inline base64.
- Move the shared `:root` tokens, header/footer, and `.scene/.scrim/.tod` rules into one `ucp.css`, and the shared scripts into one `ucp.js`; link them instead of inlining. One HTTP cache hit instead of six copies.
- Load FontAwesome 4.7 from your own assets/CDN once rather than embedding it per page.

This drops each page to a few KB of real markup and makes theme changes one-file edits.

---

## 8. Quick per-file checklist

- **login:** real auth endpoint · server-side lockout · real/removed last-sign-in notice · live server-status numbers (later) · loading transition → redirect to dashboard on success (`// production: window.location.href='dashboard'` is marked in the script) · live/removed footer clock · CSRF.
- **create:** availability endpoint (name/email) · server-side password rules · CSRF · button submits the form and only routes to `verify` on success · Turnstile (later).
- **verify:** wire the "Resend link" control to your resend endpoint (currently just a countdown).
- **reset:** POST email, keep the neutral "sent" copy · Turnstile (later).
- **newpassword:** read the token from the URL, POST `{token, password}`, server validates · CSRF.
- **verified:** static confirmation — just ensure the CTA points at your real login route.

---

*All logic strings, thresholds, and demo accounts are in the inline `<script>` at the bottom of each page and are labelled with `DEMO` comments where relevant.*
