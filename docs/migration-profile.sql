-- =====================================================================
-- BlaineSide UCP — profile page: Settings tab
--
-- Run once in phpMyAdmin (database: blainefucp) BEFORE or WITH deploying
-- the files. api/profile.php selects these columns, so the profile page
-- errors until they exist.
--
-- Additive only. Every existing account gets NULLs and behaves normally:
-- a NULL name_changed_at means "never changed, no cooldown".
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. UCP name cooldown
--
-- Unix timestamp of the last name change. The 30-day wait is measured
-- from here; NULL means the account has never changed its name and can
-- do so immediately.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN name_changed_at INT UNSIGNED DEFAULT NULL AFTER username_lower;


-- ---------------------------------------------------------------------
-- 1b. Password age
--
-- Unix timestamp of the last password change, so the Settings tab can say
-- "last changed 4 months ago". NULL on existing accounts reads as "not
-- since we started recording", which is honest — we genuinely don't know.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN password_changed_at INT UNSIGNED DEFAULT NULL;


-- ---------------------------------------------------------------------
-- 2. Email change, held until the new address confirms
--
-- The address is parked here rather than written to `email`, so the
-- current address keeps working until the link is opened. A typo can't
-- lock anyone out of their own account, and a hijacker can't move the
-- account somewhere the real owner can't reach without the owner getting
-- a warning at the address that still works.
--
-- pending_email_token stores sha256(token), matching every other token
-- on this table since Round 4 of migration.sql.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN pending_email         VARCHAR(190) DEFAULT NULL,
  ADD COLUMN pending_email_token   CHAR(64)     DEFAULT NULL,
  ADD COLUMN pending_email_expires INT UNSIGNED DEFAULT NULL;

ALTER TABLE ucp_accounts
  ADD INDEX idx_pending_email_token (pending_email_token);


-- ---------------------------------------------------------------------
-- 3. Nothing to do for lockouts
--
-- The settings endpoints reuse ucp_login_attempts with their own probe
-- values ('settings_name', 'settings_email', 'settings_password',
-- 'settings_signout', 'settings_delete'), so fumbling your password on
-- the Settings tab can't lock you out of signing in, or the reverse.
-- The `probe` column already exists from Round 3 of migration.sql.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'name_changed_at';
--   SHOW COLUMNS FROM ucp_accounts LIKE 'pending_email%';
--   SHOW COLUMNS FROM ucp_login_attempts LIKE 'probe';


-- =====================================================================
-- Not created here, on purpose
--
-- The profile page also designs Characters, the Administrative Record,
-- "Where you're signed in" and the security activity log. None of them
-- have tables yet, and api/profile.php reports them as unavailable in
-- its `features` block so the page shows an honest empty state instead
-- of sample data. When you build them:
--
--   * sessions   needs one row per session (id, uid, token hash, device,
--                ip, created, last_seen) — today there is a single
--                remember_token per account, which can only ever
--                describe one device.
--   * activity   needs a security-events table written on sign-in, 2FA
--                changes, password changes and failed attempts.
--   * record     needs the punishment tables. api/settings-delete.php
--                already looks for `ucp_punishments` and refuses to
--                delete anything if it can't find it.
-- =====================================================================
