-- =====================================================================
-- BlaineSide UCP — Staff Reports
--
-- Run once in phpMyAdmin. Select the database first (or uncomment the
-- USE line), then paste the whole thing.
--
--   USE blainefucp;
--
-- Five new tables. Nothing existing is touched.
--
-- DEPLOY ORDER: run this BEFORE or WITH the file deploy. api/_reports.php
-- degrades to "not switched on" when the tables are missing rather than
-- erroring, so a site that is one migration behind loses the feature and
-- keeps working — but the page will say so on every visit until this runs.
-- =====================================================================


-- ---------------------------------------------------------------------
-- One staff report.
--
-- Deliberately NOT modelled on ucp_appeals even though the two look alike
-- from a distance. An appeal is about a punishment the UCP already holds;
-- a staff report is about a person, and the UCP holds nothing about the
-- incident at all. Everything a handler needs has to be on the report
-- itself, which is why there are fields here an appeal doesn't have:
-- when it happened, where it happened, whether it is still happening, and
-- what the reporter actually wants done.
--
-- `handler_id` is NULL on arrival and stays NULL until somebody allocates
-- it. That is the opposite of an appeal, which is assigned on arrival to
-- whoever issued the punishment. There is no equivalent here — the obvious
-- owner of a report about a staff member is nobody in particular, and
-- auto-assigning it would hand a report to a person at random.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_reports (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Who sent it.
  account_id        INT UNSIGNED NOT NULL,

  title             VARCHAR(140) NOT NULL,

  -- Where it happened: game, forums, discord, other.
  channel           VARCHAR(16)  NOT NULL DEFAULT 'game',

  -- When, in server time. Nullable: a continuous pattern of behaviour has
  -- no single timestamp, and forcing one produces a fiction.
  incident_at       INT UNSIGNED DEFAULT NULL,

  -- 'once' or 'continuous'. The two are handled very differently — one
  -- incident is judged, a pattern is watched — so it is asked up front.
  frequency         VARCHAR(12)  NOT NULL DEFAULT 'once',

  witnesses         VARCHAR(255) DEFAULT NULL,

  -- What happened, and what the reporter wants out of it. Kept apart
  -- because they answer different questions and a handler reads them at
  -- different points.
  body              TEXT         NOT NULL,
  outcome_wanted    TEXT         DEFAULT NULL,

  -- 'pending' | 'concluded' | 'rejected'.
  status            VARCHAR(12)  NOT NULL DEFAULT 'pending',

  -- The triage Staff Management does in the first 24-48 hours:
  -- misconduct | punishment_appeal | subteam | rejected. NULL until it
  -- has been looked at, which is itself the useful state — it is how the
  -- queue knows what has not been triaged.
  category          VARCHAR(24)  DEFAULT NULL,

  -- What was actually done: action | no_action | referred | rejected.
  outcome           VARCHAR(16)  DEFAULT NULL,

  handler_id        INT UNSIGNED DEFAULT NULL,
  handler_name      VARCHAR(20)  DEFAULT NULL,
  allocated_at      INT UNSIGNED DEFAULT NULL,

  comments_enabled  TINYINT(1)   NOT NULL DEFAULT 1,

  created_at        INT UNSIGNED NOT NULL,
  updated_at        INT UNSIGNED NOT NULL,
  concluded_at      INT UNSIGNED DEFAULT NULL,
  concluded_by      INT UNSIGNED DEFAULT NULL,
  concluded_by_name VARCHAR(20)  DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_rep_account (account_id, status),
  KEY idx_rep_status (status, created_at),
  KEY idx_rep_handler (handler_id, status),
  CONSTRAINT fk_rep_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- The staff member(s) the report is about.
--
-- A table rather than a column, because one report can name more than one
-- — two administrators in the same situation is one report, not two — and
-- because this is the table the visibility rule reads: a handler may not
-- open a report that names them.
--
-- The name is stored alongside the id, the same as bulletin authors and
-- sub-group grants: a later rename must not rewrite what the report said.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_report_staff (
  report_id    INT UNSIGNED NOT NULL,
  account_id   INT UNSIGNED NOT NULL,
  name         VARCHAR(20)  NOT NULL,
  PRIMARY KEY (report_id, account_id),
  KEY idx_rs_account (account_id),
  CONSTRAINT fk_rs_report FOREIGN KEY (report_id)
    REFERENCES ucp_reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Evidence. Links only — the UCP has no file store.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_report_evidence (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id   INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  note        VARCHAR(190) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rev_report (report_id),
  CONSTRAINT fk_rev_report FOREIGN KEY (report_id)
    REFERENCES ucp_reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- The thread. `staff_only` is how Staff Management talks among themselves
-- on a report without opening a second place to look.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_report_comments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id       INT UNSIGNED NOT NULL,
  author_id       INT UNSIGNED DEFAULT NULL,
  author_name     VARCHAR(20)  NOT NULL,
  author_is_staff TINYINT(1)   NOT NULL DEFAULT 0,
  staff_only      TINYINT(1)   NOT NULL DEFAULT 0,
  body            TEXT         NOT NULL,
  created_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rcm_report (report_id, created_at),
  CONSTRAINT fk_rcm_report FOREIGN KEY (report_id)
    REFERENCES ucp_reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- The running log. Who opened it, who allocated it, who decided it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ucp_report_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  report_id   INT UNSIGNED NOT NULL,
  actor_id    INT UNSIGNED DEFAULT NULL,
  actor_name  VARCHAR(20)  NOT NULL,
  action      VARCHAR(24)  NOT NULL,
  detail      VARCHAR(255) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rlg_report (report_id, created_at),
  CONSTRAINT fk_rlg_report FOREIGN KEY (report_id)
    REFERENCES ucp_reports (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_report%';
--   SELECT COUNT(*) FROM ucp_reports;
--
-- All five start empty. Until somebody sends a report, nothing about the
-- UCP behaves differently — except that /dashboard/reports stops saying
-- "not switched on yet".
-- =====================================================================
