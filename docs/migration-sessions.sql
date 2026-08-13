-- =====================================================================
-- BlaineSide UCP — "Where you're signed in" and "Recent security activity"
--
-- Run once in phpMyAdmin (database: blainefucp), the same way as
-- migration-profile.sql. Two new tables, nothing existing is altered.
--
-- Until now the UCP could only ever describe ONE device: there was a
-- single remember_token per account and nothing else recorded. These
-- tables are what make a real device list and a real activity log
-- possible — one row per session, one row per security event.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. Sessions
--
-- One row per signed-in browser. `id` is a random token we generate at
-- sign-in and keep in $_SESSION — NOT the PHP session id, which changes
-- every time session_regenerate_id() runs (sign-in, password change) and
-- would orphan the row each time.
--
-- revoked_at is what makes "sign this device out" work: every request
-- checks the row, and a revoked session is destroyed on its very next
-- one, wherever it is. That is the piece session_epoch could never do on
-- its own — epoch is all-or-nothing, this is per device.
--
-- ip is stored in full because it is the only thing that makes "that
-- wasn't me" reportable; it is masked before it ever reaches the page.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_sessions (
  id               CHAR(32)     NOT NULL,
  account_id       INT UNSIGNED NOT NULL,
  device           VARCHAR(120) DEFAULT NULL,   -- "Chrome on Windows"
  user_agent       VARCHAR(255) DEFAULT NULL,
  ip               VARCHAR(45)  DEFAULT NULL,
  remembered       TINYINT(1)   NOT NULL DEFAULT 0,
  created_at       INT UNSIGNED NOT NULL,
  last_seen        INT UNSIGNED NOT NULL,
  revoked_at       INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_sessions_account (account_id, last_seen),
  KEY idx_sessions_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2. Security activity
--
-- Append-only. Written on sign-ins, failed sign-ins, and every change to
-- how the account is reached — password, email, two-step, name, sessions.
--
-- account_id is NOT a foreign key on purpose: a failed sign-in against a
-- name that doesn't exist still deserves a row, and deleting an account
-- must not silently erase the history of what was done to it.
--
-- `level` drives nothing but the colour of the dot on the page:
--   info  ordinary
--   good  something got safer (2FA on, password changed)
--   warn  worth a second look (failed attempts, 2FA off)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_security_log (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id   INT UNSIGNED    NOT NULL,
  event        VARCHAR(40)     NOT NULL,
  detail       VARCHAR(190)    DEFAULT NULL,
  level        ENUM('info','good','warn') NOT NULL DEFAULT 'info',
  device       VARCHAR(120)    DEFAULT NULL,
  ip           VARCHAR(45)     DEFAULT NULL,
  created_at   INT UNSIGNED    NOT NULL,
  PRIMARY KEY (id),
  KEY idx_seclog_account (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Housekeeping
--
-- api/_sessions.php prunes both tables at sign-in: sessions untouched for
-- 30 days, log entries older than 90. Nothing else needs scheduling.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_sessions';
--   SHOW TABLES LIKE 'ucp_security_log';
--
-- Then sign out and back in: SELECT * FROM ucp_sessions; should have one
-- row, and ucp_security_log one 'signin' entry.
