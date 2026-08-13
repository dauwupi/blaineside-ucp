-- =====================================================================
-- BlaineSide UCP — Dashboard announcements
--
-- Run once in phpMyAdmin (database: blainefucp). One new table.
--
-- The strip across the top of the dashboard. One is live at a time, by
-- design: an announcement that has to compete with three others isn't an
-- announcement. Activating one stands the others down automatically.
-- =====================================================================


CREATE TABLE IF NOT EXISTS ucp_announcements (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Sets the chip label and the colour of the strip:
  --   notice       gold      — the everyday one
  --   maintenance  blue      — downtime, planned work
  --   warning      orange    — something players should act on
  --   critical     red       — outage, emergency, read this now
  --   success      green     — it's back, it's fixed, it's done
  type        ENUM('notice','maintenance','warning','critical','success')
              NOT NULL DEFAULT 'notice',

  -- The bold opening clause, then the rest. Split so the strip can lead
  -- with the fact and follow with the detail, which is how the design
  -- reads: "Scheduled maintenance this Sunday, 03:00 UTC." then the
  -- explanation.
  lead        VARCHAR(120) NOT NULL,
  body        VARCHAR(240) DEFAULT NULL,

  -- Optional "read more" target for the strip.
  link        VARCHAR(500) DEFAULT NULL,

  -- Whether players may dismiss it. A critical outage notice usually
  -- shouldn't be dismissable; a reminder should.
  dismissable TINYINT(1)   NOT NULL DEFAULT 1,

  -- Exactly one row may have this set. api/announcement-activate.php
  -- clears every other row in the same transaction.
  active      TINYINT(1)   NOT NULL DEFAULT 0,

  author_id   INT UNSIGNED DEFAULT NULL,
  author_name VARCHAR(20)  NOT NULL,
  created_at  INT UNSIGNED NOT NULL,
  updated_at  INT UNSIGNED DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_ann_active (active, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_announcements';
--
-- Starts empty, so the dashboard shows no strip at all until Management
-- publishes and activates one.
-- =====================================================================
