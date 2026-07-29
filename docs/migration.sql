-- =====================================================================
-- BlaineSide UCP — schema changes for the redesigned auth pages
-- Run once in phpMyAdmin (database: blainefucp) after deploying the files.
--
-- Adds:
--   1. password reset tokens          (reset.html / reset-confirm.html)
--   2. per-device "last sign-in" hash (the notice on the login page)
--   3. server-side login lockout      (replaces the browser-only countdown)
--
-- Safe to run on the live table: every statement is additive, no data is
-- dropped or rewritten. Existing accounts get NULLs and behave normally.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1 + 2. New columns on ucp_accounts
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN reset_token   VARCHAR(64)      DEFAULT NULL AFTER verify_token,
  ADD COLUMN reset_expires INT UNSIGNED     DEFAULT NULL AFTER reset_token,
  ADD COLUMN last_device   CHAR(64)         DEFAULT NULL AFTER last_login;

-- Reset tokens are looked up directly, so give them an index.
ALTER TABLE ucp_accounts
  ADD INDEX idx_reset_token (reset_token);


-- ---------------------------------------------------------------------
-- 1b. Remember-me columns
--
-- The login code has always written these, but no earlier migration
-- created them — so "Remember me" could never work, and on some setups a
-- successful sign-in came back as "Incorrect UCP name or password".
-- If they already exist, MySQL errors harmlessly and you can skip this.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN remember_token   VARCHAR(64)  DEFAULT NULL AFTER reset_expires,
  ADD COLUMN remember_expires INT UNSIGNED DEFAULT NULL AFTER remember_token;

ALTER TABLE ucp_accounts
  ADD INDEX idx_remember_token (remember_token);

-- Notes:
--   reset_token    64-char hex, single use, cleared on use/expiry.
--   reset_expires  unix timestamp; 30 minutes after issue.
--   last_device    sha256 of the device cookie, so the login page can say
--                  "from this device". Never stores the raw token.


-- ---------------------------------------------------------------------
-- 3. Login lockout, tracked per account + IP
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id   INT UNSIGNED DEFAULT NULL,      -- NULL = attempts against an unknown name
  ip           VARCHAR(45)  NOT NULL,          -- IPv4 or IPv6
  fails        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  lock_level   TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- escalation step: 30s -> 5m -> 15m
  locked_until INT UNSIGNED NOT NULL DEFAULT 0,      -- unix timestamp, 0 = not locked
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_account_ip (account_id, ip),
  KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional housekeeping — clears rows nobody has touched in a week.
-- Run occasionally, or add it to the existing hourly cron.
-- DELETE FROM ucp_login_attempts
--  WHERE locked_until < UNIX_TIMESTAMP() AND updated_at < NOW() - INTERVAL 7 DAY;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
-- Expect reset_token, reset_expires and last_device in the column list:
--   SHOW COLUMNS FROM ucp_accounts;
-- Expect an empty table (it fills as people mistype passwords):
--   SELECT * FROM ucp_login_attempts;


-- =====================================================================
-- Round 2 — hardening (run once, after the reset/remember-me migration)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 4. Verification links now expire
--
-- Previously a verify_token stayed valid forever, so an old inbox or a
-- leaked mail archive remained a permanent key to the account. New links
-- are valid for 48 hours. Existing pending accounts get NULL, which is
-- treated as "no expiry recorded" and still works — use Resend to get a
-- link with an expiry attached.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN verify_expires INT UNSIGNED DEFAULT NULL AFTER verify_token;


-- ---------------------------------------------------------------------
-- 5. Rate limiting that can't be bypassed by dropping cookies
--
-- The old limiter counted in $_SESSION, so a script that discarded its
-- cookie looked like a new visitor every request — leaving the password
-- reset and resend endpoints usable to spam mail at any known address.
-- This table keys the count on IP + action instead.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_rate_limits (
  action       VARCHAR(32)  NOT NULL,          -- 'login', 'reset', 'resend', …
  ip           VARCHAR(45)  NOT NULL,          -- IPv4 or IPv6
  window_start INT UNSIGNED NOT NULL,          -- unix ts of the current minute
  hits         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (action, ip),
  KEY idx_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional housekeeping — rows go stale after a minute.
-- DELETE FROM ucp_rate_limits WHERE window_start < UNIX_TIMESTAMP() - 3600;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'verify_expires';
--   SELECT * FROM ucp_rate_limits;


-- =====================================================================
-- Round 3 — audit fixes (run once)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 6. Password reset now ends sessions on every other device
--
-- Previously a reset only ended the session of the browser doing the
-- resetting. Someone holding a stolen session cookie stayed signed in
-- through a password change — the exact moment it most needs to work.
-- Each reset bumps session_epoch; session.php refuses any session issued
-- under an older value.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN session_epoch INT UNSIGNED NOT NULL DEFAULT 0;


-- ---------------------------------------------------------------------
-- 7. Per-name lockout buckets (closes username enumeration)
--
-- Failed logins for names that don't exist all shared one bucket per IP
-- (account_id = NULL). Lock it with three junk names and you had an
-- oracle: real names answered 401, fake ones 429. Each unknown name now
-- gets its own bucket, keyed on a hash of the submitted name.
--
-- Existing rows are cleared: they were counted under the old scheme.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_login_attempts
  ADD COLUMN probe CHAR(64) NOT NULL DEFAULT '' AFTER ip;

ALTER TABLE ucp_login_attempts DROP INDEX uq_account_ip;
ALTER TABLE ucp_login_attempts ADD UNIQUE KEY uq_bucket (account_id, ip, probe);

DELETE FROM ucp_login_attempts;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'session_epoch';
--   SHOW COLUMNS FROM ucp_login_attempts LIKE 'probe';


-- =====================================================================
-- Round 4 — tokens are now stored hashed (NO schema change needed)
-- =====================================================================
--
-- verify_token, reset_token and remember_token now hold sha256(token)
-- instead of the token itself. The column types already fit (64 hex
-- chars), so there is nothing to ALTER.
--
-- Existing tokens were issued in plaintext and will no longer match, so
-- clear them once when you deploy. Effect on people:
--   * anyone signed in via "remember me" signs in again (normal sessions
--     are unaffected)
--   * unverified accounts need a fresh link — the Resend button on the
--     verify page issues one
--   * any outstanding reset link stops working; request a new one
--     (they only lived 30 minutes anyway)
--
-- Skip this and those tokens simply never match — nothing breaks, the
-- rows just sit there dead. Running it is tidier.

UPDATE ucp_accounts
   SET verify_token    = NULL,
       reset_token     = NULL,
       reset_expires   = NULL,
       remember_token  = NULL,
       remember_expires = NULL;
