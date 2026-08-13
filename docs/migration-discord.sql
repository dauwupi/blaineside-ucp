-- =====================================================================
-- BlaineSide UCP — verified Discord linking
--
-- Run once in phpMyAdmin (database: blainefucp), like the others.
-- Additive: the existing `discord` column is left exactly as it is.
--
-- Why both columns:
--   discord            what the player TYPED at sign-up. Unverified, and
--                      frequently a typo, an old handle, or someone else's.
--   discord_id         what Discord itself told us, after the player signed
--                      in to Discord and approved the link.
--
-- Keeping the typed one means nobody loses the value they entered, and the
-- profile page can be honest about which of the two it is showing.
-- =====================================================================


ALTER TABLE ucp_accounts
  ADD COLUMN discord_id        VARCHAR(32)  DEFAULT NULL,
  ADD COLUMN discord_username  VARCHAR(64)  DEFAULT NULL,
  ADD COLUMN discord_linked_at INT UNSIGNED DEFAULT NULL;


-- One Discord account, one UCP.
--
-- Without this, one person can link the same Discord to several UCPs — which
-- is exactly what someone evading a ban would do, and it would make "who is
-- this on Discord?" ambiguous for staff at the worst possible moment.
-- NULLs don't collide in a MySQL unique index, so every unlinked account is
-- unaffected.
ALTER TABLE ucp_accounts
  ADD UNIQUE KEY uniq_discord_id (discord_id);


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'discord%';
--
-- Four rows: discord, discord_id, discord_username, discord_linked_at.
--
-- The page still shows "Linking not available yet" until the Discord
-- application's client id and secret are in api/config.php — see the
-- 'discord' block in api/config.example.php.
-- =====================================================================
