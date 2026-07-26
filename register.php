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
  ],

  // ---- Security ----
  // Allowed origin for the browser fetch() calls (CORS). Should match base_url.
  'allowed_origin' => 'https://ucp.blaineside.com',
];
