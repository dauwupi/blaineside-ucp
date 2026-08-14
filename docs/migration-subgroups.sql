-- =====================================================================
-- BlaineSide UCP — administrator sub-groups
--
-- Run once in phpMyAdmin. Select the database first (or uncomment the
-- USE line), then paste the whole thing.
--
--   USE blainefucp;
--
-- One new table. Nothing existing is touched.
-- =====================================================================


-- ---------------------------------------------------------------------
-- Sub-groups held by an administrator.
--
-- A sub-group is a department, not a rung: Staff Management, Faction
-- Management, Property Management. They sit alongside the group ladder
-- rather than inside it — an Admin Lvl 2 with Staff Management can do
-- things a Senior Admin without it cannot, which is exactly the point.
--
-- A row per account per sub-group, rather than a column of flags or a
-- bitmask, for three reasons:
--
--   1. Somebody can hold more than one. Two rows says that plainly;
--      a bitmask says 5 and needs a lookup table to read.
--   2. Adding a fourth sub-group later is an entry in api/_teams.php.
--      No ALTER TABLE, no migration, no downtime.
--   3. Who granted it and when are worth keeping, and they belong on
--      the grant itself. A flags column has nowhere to put them.
--
-- The rank band (Trainee Admin .. Lead Admin) is enforced in
-- api/_teams.php, not here. A CHECK constraint would be a rule the
-- database can state but not explain, and the person who just tried to
-- give Staff Management to a Manager needs the explanation.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_account_teams (
  account_id       INT UNSIGNED NOT NULL,

  -- Matches a key in BS_TEAMS (api/_teams.php): staff_management,
  -- faction_management, property_management.
  team             VARCHAR(32)  NOT NULL,

  -- Who handed it out. The name is stored as well as the id so a later
  -- rename doesn't rewrite history, the same as bulletin authors.
  granted_by       INT UNSIGNED DEFAULT NULL,
  granted_by_name  VARCHAR(20)  DEFAULT NULL,
  granted_at       INT UNSIGNED NOT NULL,

  PRIMARY KEY (account_id, team),
  KEY idx_teams_team (team),

  -- Losing the account takes its sub-groups with it. There is nothing
  -- meaningful in a grant to somebody who no longer exists.
  CONSTRAINT fk_teams_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_account_teams';
--
--   SELECT a.username, rank_names.n AS grp, t.team, t.granted_by_name
--     FROM ucp_account_teams t
--     JOIN ucp_accounts a ON a.id = t.account_id
--     LEFT JOIN (SELECT 0 n) rank_names ON 1=0;   -- (group name is in PHP)
--
-- Simpler:
--   SELECT a.username, a.admin_rank, t.team FROM ucp_account_teams t
--     JOIN ucp_accounts a ON a.id = t.account_id;
--
-- The table starts empty. Until somebody is given a sub-group, nothing
-- about the UCP behaves differently.
-- =====================================================================
