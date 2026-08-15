-- =====================================================================
-- BlaineSide UCP — notifications
--
-- Run once in phpMyAdmin. Select the database first, or uncomment:
--
--   USE blainefucp;
--
-- One new table. Nothing existing is touched.
--
-- Everything degrades to "no notifications" when this hasn't been run —
-- the bell simply never lights up — so the file deploy is safe either way.
-- =====================================================================


-- ---------------------------------------------------------------------
-- One notification, for one person.
--
-- A row per recipient rather than a row per event with a join table. An
-- event that goes to three people is three rows, which is more storage and
-- far less code: "mine, unread first" is one indexed query, and marking one
-- read cannot accidentally mark it read for somebody else.
--
-- `url` is where it goes when clicked, stored on the row rather than
-- rebuilt from kind+ref. Appeal #12 stays reachable at the URL it had when
-- the notification was written, even if the routes move.
--
-- `dedupe` collapses a burst: five comments on the same appeal before
-- anyone opens it is one notification that says so, not five. Null means
-- never collapse.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_notifications (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id  INT UNSIGNED NOT NULL,

  -- appeal | report — what area it came from, for the icon and for
  -- filtering later.
  area        VARCHAR(16)  NOT NULL DEFAULT 'system',

  -- submitted | comment | verdict | allocated | overruled | …
  kind        VARCHAR(24)  NOT NULL,

  title       VARCHAR(190) NOT NULL,
  body        VARCHAR(255) DEFAULT NULL,
  url         VARCHAR(190) DEFAULT NULL,

  -- Who caused it, for "X replied". Never a rank or an id the reader
  -- shouldn't have — on a staff report the handler's name is omitted.
  actor_name  VARCHAR(20)  DEFAULT NULL,

  dedupe      VARCHAR(64)  DEFAULT NULL,

  created_at  INT UNSIGNED NOT NULL,
  read_at     INT UNSIGNED DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_note_acct (account_id, read_at, created_at),
  KEY idx_note_dedupe (account_id, dedupe, read_at),
  CONSTRAINT fk_note_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_notifications';
--   SELECT COUNT(*) FROM ucp_notifications WHERE read_at IS NULL;
-- =====================================================================
