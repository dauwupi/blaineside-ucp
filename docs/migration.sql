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
