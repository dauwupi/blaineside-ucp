-- =====================================================================
-- BlaineSide UCP — punishments and ban appeals
--
-- Run once in phpMyAdmin. Select the database first, or keep the USE
-- line below and paste the whole thing.
--
-- Five tables. Nothing existing is altered, and nothing is punished or
-- appealed by running it — every table starts empty.
--
-- Why a punishments table at all, when the server ban system isn't
-- connected yet: an appeal has to point AT something. Without a row to
-- attach to, "Ban details" is just the appellant's own account of why
-- they were banned, and the staff member deciding has nothing to check
-- it against. So the record comes first and the sources fill it in as
-- they arrive — user locks already, game bans when the server is linked,
-- forum and Discord bans typed in by staff meanwhile.
-- =====================================================================

USE blainefucp;


-- ---------------------------------------------------------------------
-- 1. Punishments
--
-- One row per punishment ever issued, active or not. Not a column on
-- ucp_accounts: an account can hold a game ban and a Discord ban at the
-- same time, they are lifted independently, and the history of both is
-- what an appeal is judged on.
--
-- `kind` is the platform, and it is also the answer to "what can this
-- person appeal": the appeal form's first question is a set of these.
-- Kicks and warnings are deliberately absent — they are not appealable,
-- so they do not belong in the table the appeal form reads.
--
-- `appealable` exists for egregious violations. A ban for doxxing or
-- real-life threats is not open to appeal, and the player should be told
-- that on the page rather than after writing five paragraphs.
--
-- `external_ref` is the game server's own ban id, once there is one.
-- Nullable and unused today; it is here so the import doesn't need a
-- migration of its own.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_punishments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id       INT UNSIGNED NOT NULL,

  -- game_ban | forum_ban | discord_ban | user_lock  (api/_punish.php)
  kind             VARCHAR(24)  NOT NULL,

  -- A permanent punishment has no expiry. A temporary one has both:
  -- permanent = 0 and an expires_at in the future.
  permanent        TINYINT(1)   NOT NULL DEFAULT 1,
  expires_at       INT UNSIGNED DEFAULT NULL,

  reason           VARCHAR(255) DEFAULT NULL,

  -- Who issued it. Name as well as id, so a later rename doesn't rewrite
  -- history — the same rule bulletins and sub-groups follow.
  issued_by        INT UNSIGNED DEFAULT NULL,
  issued_by_name   VARCHAR(20)  DEFAULT NULL,
  issued_at        INT UNSIGNED NOT NULL,

  -- Whether it is in force right now. Kept as a column rather than
  -- derived from expires_at because a punishment can also be lifted
  -- early, and "lifted" and "expired" are different facts.
  active           TINYINT(1)   NOT NULL DEFAULT 1,
  lifted_at        INT UNSIGNED DEFAULT NULL,
  lifted_by        INT UNSIGNED DEFAULT NULL,
  lifted_by_name   VARCHAR(20)  DEFAULT NULL,
  lifted_reason    VARCHAR(255) DEFAULT NULL,

  -- 0 for egregious violations: the appeal form refuses them by name.
  appealable       TINYINT(1)   NOT NULL DEFAULT 1,

  -- The issuing system's own id, when there is one (game server ban id).
  external_ref     VARCHAR(64)  DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_pun_account (account_id, active),
  KEY idx_pun_kind    (kind, active),

  CONSTRAINT fk_pun_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 2. Appeals
--
-- One row per appeal. `punishment_id` is what is being appealed;
-- `platforms` is what the player ticked, kept separately because the two
-- can honestly disagree — somebody banned in game may believe they were
-- also banned on Discord, and staff need to see what they claimed as
-- well as what is on file.
--
-- status starts 'pending' and there is no way to set it back. An
-- administrator concludes an appeal as accepted or rejected, with a
-- mandatory comment either way — see api/appeal-verdict.php. Pending is
-- a state, not a decision, which is why it isn't in the dropdown.
--
-- comments_enabled lets a handler close an appeal to further replies
-- without concluding it, for the case where a player is using the thread
-- to argue rather than to add anything.
--
-- character_id is nullable and unused: characters aren't linked to the
-- UCP yet. The column exists so the field on the form can start working
-- without a migration the day they are.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_appeals (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id       INT UNSIGNED NOT NULL,
  punishment_id    INT UNSIGNED DEFAULT NULL,

  -- Comma-separated subset of: game, discord, forums
  platforms        VARCHAR(48)  NOT NULL DEFAULT '',

  character_id     INT UNSIGNED DEFAULT NULL,

  body             TEXT         NOT NULL,

  -- pending | accepted | rejected
  status           VARCHAR(12)  NOT NULL DEFAULT 'pending',

  handler_id       INT UNSIGNED DEFAULT NULL,
  handler_name     VARCHAR(20)  DEFAULT NULL,

  comments_enabled TINYINT(1)   NOT NULL DEFAULT 1,

  created_at       INT UNSIGNED NOT NULL,
  updated_at       INT UNSIGNED NOT NULL,

  concluded_at     INT UNSIGNED DEFAULT NULL,
  concluded_by     INT UNSIGNED DEFAULT NULL,
  concluded_by_name VARCHAR(20) DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_app_account (account_id, status),
  KEY idx_app_status  (status, created_at),
  KEY idx_app_handler (handler_id, status),

  CONSTRAINT fk_app_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 3. Evidence
--
-- Links, not uploads. The UCP has no file store, and a screenshot host
-- link is what players already paste into Discord — asking for anything
-- else would mean most appeals arrive with no evidence at all.
--
-- `note` is the "brief description of what's in the evidence" the rules
-- ask for. Optional, because an empty note is better than a made-up one.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_appeal_evidence (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id   INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  note        VARCHAR(190) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  KEY idx_ev_appeal (appeal_id),

  CONSTRAINT fk_ev_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 4. Comments
--
-- staff_only is the important column. A staff-only comment is never
-- returned to the appellant by api/appeal.php — not hidden by the page,
-- absent from the response — so a mistake in the front end cannot leak
-- one. Staff talk to each other in the same thread they talk to the
-- player in, which is what stops the real discussion happening somewhere
-- with no record.
--
-- author_is_staff is stored rather than looked up, because a staff
-- member who later resigns wrote that comment as staff.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_appeal_comments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id       INT UNSIGNED NOT NULL,
  author_id       INT UNSIGNED DEFAULT NULL,
  author_name     VARCHAR(20)  NOT NULL,
  author_is_staff TINYINT(1)   NOT NULL DEFAULT 0,
  staff_only      TINYINT(1)   NOT NULL DEFAULT 0,
  body            TEXT         NOT NULL,
  created_at      INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  KEY idx_cm_appeal (appeal_id, created_at),

  CONSTRAINT fk_cm_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 5. Running log
--
-- Every staff view and every staff action on an appeal. Administrators
-- only — it is never returned to the appellant.
--
-- Views are recorded as well as changes, deliberately. "Who has looked
-- at this and not acted" is the question a Staff Manager actually asks
-- about a three-week-old pending appeal, and a log of changes alone
-- cannot answer it.
--
-- Repeat views by the same person within the hour are collapsed by
-- api/_appeals.php rather than here, so a refresh doesn't bury the
-- thread in identical lines.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_appeal_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id   INT UNSIGNED NOT NULL,
  actor_id    INT UNSIGNED DEFAULT NULL,
  actor_name  VARCHAR(20)  NOT NULL,

  -- viewed | commented | verdict | handler | comments | evidence
  action      VARCHAR(24)  NOT NULL,
  detail      VARCHAR(255) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,

  PRIMARY KEY (id),
  KEY idx_lg_appeal (appeal_id, created_at),

  CONSTRAINT fk_lg_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- 6. Backfill: existing user locks become punishment rows
--
-- Anyone locked before this migration ran has a lock on their account
-- and nothing to appeal against. This gives them one. Safe to re-run —
-- the NOT EXISTS keeps it from inserting twice.
--
-- Requires docs/migration-userlock.sql to have been run first. If the
-- lock columns don't exist yet this statement errors harmlessly and can
-- be skipped.
-- ---------------------------------------------------------------------
INSERT INTO ucp_punishments
  (account_id, kind, permanent, reason, issued_by, issued_by_name, issued_at, active)
SELECT a.id, 'user_lock', 1, a.lock_reason, a.locked_by, a.locked_by_name,
       COALESCE(a.locked_at, UNIX_TIMESTAMP()), 1
  FROM ucp_accounts a
 WHERE a.status = 'locked'
   AND NOT EXISTS (
     SELECT 1 FROM ucp_punishments p
      WHERE p.account_id = a.id AND p.kind = 'user_lock' AND p.active = 1
   );


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_appeal%';
--   SHOW TABLES LIKE 'ucp_punishments';
--
--   SELECT p.id, a.username, p.kind, p.permanent, p.reason, p.active
--     FROM ucp_punishments p JOIN ucp_accounts a ON a.id = p.account_id;
--
-- To enter a forum or Discord ban by hand until those systems are
-- connected (replace the username and the reason):
--
--   INSERT INTO ucp_punishments
--     (account_id, kind, permanent, reason, issued_by_name, issued_at, active)
--   SELECT id, 'discord_ban', 1, 'Reason goes here', 'YourName',
--          UNIX_TIMESTAMP(), 1
--     FROM ucp_accounts WHERE username = 'somebody';
--
-- To mark one as not open to appeal:
--   UPDATE ucp_punishments SET appealable = 0 WHERE id = 1;
-- =====================================================================
