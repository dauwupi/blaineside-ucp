-- =====================================================================
-- BlaineSide UCP — two-factor authentication (TOTP)
--
-- Run once in phpMyAdmin (database: blainefucp) after deploying the files.
-- Every statement is additive: no data is dropped or rewritten, and every
-- existing account carries on signing in exactly as before with 2FA off.
--
-- Run this BEFORE deploying, or at the same time. api/login.php selects the
-- new columns, so it errors on every sign-in until they exist.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. TOTP columns on ucp_accounts
--
--   totp_secret      The shared secret. 32 chars if stored as plain base32,
--                    ~85 once encrypted with security.secret_key — hence 255.
--   totp_enabled     The single flag login.php gates on.
--   totp_last_step   The last 30-second time step accepted for this account.
--                    Codes at or below it are refused, so a code that was
--                    watched over someone's shoulder can't be replayed inside
--                    its own validity window.
--   totp_enabled_at  When it was switched on. Purely for support ("when did
--                    you set this up?").
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN totp_secret     VARCHAR(255)     DEFAULT NULL AFTER last_device,
  ADD COLUMN totp_enabled    TINYINT(1)       NOT NULL DEFAULT 0 AFTER totp_secret,
  ADD COLUMN totp_last_step  BIGINT UNSIGNED  NOT NULL DEFAULT 0 AFTER totp_enabled,
  ADD COLUMN totp_enabled_at DATETIME         DEFAULT NULL AFTER totp_last_step;


-- ---------------------------------------------------------------------
-- 2. Recovery codes
--
-- Ten per account, single use. Stored as sha256('<uid>:<CODE>') — salted
-- with the account id so one candidate can't be tested against every row in
-- a leaked dump at once. Codes carry ~59 bits of entropy from random_int, so
-- there is nothing to guess and no need for a slow KDF.
--
-- Spent codes are marked with used_at rather than deleted, so "they used a
-- recovery code on the 3rd" is still answerable afterwards.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_2fa_backup_codes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid        INT UNSIGNED NOT NULL,
  code_hash  CHAR(64)     NOT NULL,
  created_at INT UNSIGNED NOT NULL DEFAULT 0,
  used_at    INT UNSIGNED DEFAULT NULL,     -- unix ts, NULL = still usable
  PRIMARY KEY (id),
  -- The lookup in twofa_consume_backup_code() is (uid, code_hash); unique
  -- because the same code must never exist twice for one account.
  UNIQUE KEY uq_uid_code (uid, code_hash),
  KEY idx_uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------
-- 3. Nothing to do for lockouts
--
-- Code attempts reuse ucp_login_attempts with probe = '2fa' (sign-in prompt)
-- and probe = '2fa_settings' (the security page). The `probe` column already
-- exists from Round 3 of migration.sql — if you have not run that yet, run it
-- first or the 2FA lockout silently shares a bucket with password attempts.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- Optional housekeeping
-- ---------------------------------------------------------------------
-- Recovery codes for accounts that no longer exist:
--   DELETE b FROM ucp_2fa_backup_codes b
--    LEFT JOIN ucp_accounts a ON a.id = b.uid
--    WHERE a.id IS NULL;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'totp%';
--   SHOW COLUMNS FROM ucp_login_attempts LIKE 'probe';
--   SELECT * FROM ucp_2fa_backup_codes;          -- empty until someone opts in
--   SELECT username, totp_enabled FROM ucp_accounts WHERE totp_enabled = 1;


-- =====================================================================
-- Locking someone out, and getting them back in
--
-- If a user loses their phone AND their recovery codes, there is no self
-- -service route back in — that is the point of a second factor. A Founder
-- clears it by hand:
--
--   UPDATE ucp_accounts
--      SET totp_secret = NULL, totp_enabled = 0, totp_last_step = 0,
--          totp_enabled_at = NULL
--    WHERE username_lower = 'theirname';
--   DELETE FROM ucp_2fa_backup_codes
--    WHERE uid = (SELECT id FROM ucp_accounts WHERE username_lower = 'theirname');
--
-- Verify who you are talking to before running that. It is the whole
-- security model in two statements.
-- =====================================================================
