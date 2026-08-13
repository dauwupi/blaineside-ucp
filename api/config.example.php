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

  // ---- Forum ----
  'forum' => [
    // Used to build the "open your forum profile" link on the profile page.
    'url' => 'https://forum.blaineside.com',
  ],
];
