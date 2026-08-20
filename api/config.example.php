<?php
/**
 * BlaineSide UCP — configuration
 *
 * COPY this file to `config.php` and fill in your real values.
 * `config.php` must NEVER be committed to GitHub (see .gitignore).
 * Upload `config.php` directly to the server via FTP.
 */

return [
  // ---- Database (OVH → Databases tab → your blainefucp row) ----
  'db' => [
    'host' => 'blainefucp.mysql.db',   // "Server address" column
    'name' => 'blainefucp',            // "Database name" column
    'user' => 'blainefucp',            // "Username" column
    'pass' => 'YOUR_DB_PASSWORD_HERE',
    'charset' => 'utf8mb4',
  ],

  // ---- SMTP (your OVH mailbox: noreply@blaineside.com) ----
  'smtp' => [
    'host'      => 'ssl0.ovh.net',
    'port'      => 465,                 // 465 = SSL, 587 = STARTTLS
    'secure'    => 'ssl',              // 'ssl' for 465, 'tls' for 587
    'user'      => 'noreply@blaineside.com',
    'pass'      => 'YOUR_MAILBOX_PASSWORD_HERE',
    'from_email'=> 'noreply@blaineside.com',
    'from_name' => 'BlaineSide',
  ],

  // ---- Site ----
  'site' => [
    // Public base URL of the UCP (no trailing slash). Used to build the
    // verification link in emails.
    'base_url' => 'https://ucp.blaineside.com',

    // Shown as the account issuer inside authenticator apps, so people can
    // tell this entry apart from every other 6-digit code on their phone.
    // Changing it later does NOT break existing setups — the app keeps the
    // label it was given when the code was scanned.
    'name' => 'BlaineSide UCP',
  ],

  // ---- Security ----
  // Allowed origin for the browser fetch() calls (CORS). Should match base_url.
  'allowed_origin' => 'https://ucp.blaineside.com',

  'security' => [
    // ---- 2FA secret encryption (recommended, optional) ----
    //
    // Encrypts every stored TOTP secret with AES-256-GCM. The key lives here,
    // in the one file that is gitignored and never appears in a database
    // dump — so a leaked dump yields no working second factors.
    //
    // Generate one and paste the whole line it prints:
    //     php -r "echo 'base64:' . base64_encode(random_bytes(32)), PHP_EOL;"
    //
    // Leave it '' and secrets are stored as plain base32 — everything still
    // works, you just lose that layer.
    //
    // WARNING: once accounts have 2FA enabled, changing or losing this key
    // makes their secrets unreadable and locks them out. Back it up wherever
    // you keep the database password.
    'secret_key' => '',

    // ---- Mandatory 2FA by rank (off by default) ----
    //
    // null  = optional for everyone (current behaviour)
    // 1     = every staff member (Support Staff and above) must enable it
    // 9     = Founders only
    //
    // Accounts at or above this rank are sent to the Security tab on sign-in
    // until 2FA is on, and cannot switch it back off. Members are never affected.
    'totp_required_rank' => null,

    // ---- Self-service account deletion (off) ----
    //
    // A player may not delete their UCP while anything sits on their
    // administrative record — otherwise anyone facing a ban wipes their
    // history and comes back clean. That record system does not exist yet,
    // so api/settings-delete.php refuses outright and the Settings tab shows
    // the button as unavailable.
    //
    // Turn this on only once the punishment tables are live AND the check in
    // settings-delete.php points at them. With the flag on and the tables
    // missing, the endpoint fails closed rather than letting anyone through.
    'allow_self_delete' => false,
  ],

  // ---- Discord account linking ----
  //
  // Leave the two values empty and the profile page shows "Linking not
  // available yet" — nothing breaks, the button simply isn't offered.
  //
  // To turn it on:
  //   1. https://discord.com/developers/applications -> New Application
  //   2. OAuth2 -> copy the Client ID, then Reset Secret and copy that
  //   3. OAuth2 -> Redirects -> add EXACTLY the redirect_uri below. Discord
  //      compares it character for character; a missing /api or a trailing
  //      slash is the usual cause of "invalid redirect_uri".
  //
  // The UCP only ever asks for the `identify` scope — the account id and
  // username. The access token is used once and revoked in the same request,
  // so nothing about the player's Discord is retained or actionable.
  'discord' => [
    'client_id'     => '',
    'client_secret' => '',
    'redirect_uri'  => 'https://ucp.blaineside.com/api/discord-callback.php',
  ],

  // ---- Game server link ----
  //
  // Shared secret the FiveM server sends to api/game-verify.php, which is how
  // in-game logins are checked against this UCP. Nothing else uses it.
  //
  // It exists because the game server cannot reach the database directly:
  // OVH's included databases only accept connections from inside their
  // network. game-verify.php runs where the database IS reachable, so the
  // game server never needs database credentials — only this secret.
  //
  // Generate one and paste it here AND in the server's server.cfg as
  // `set ucp_internal_secret "..."`. They must match exactly.
  //     php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
  //
  // Leave it '' and api/game-verify.php refuses every request, which is the
  // correct default for a UCP with no game server attached yet.
  'game' => [
    'internal_secret' => '',
  ],

  // ---- Forum ----
  'forum' => [
    // Used to build the "open your forum profile" link on the profile page.
    'url' => 'https://forum.blaineside.com',
  ],
];
