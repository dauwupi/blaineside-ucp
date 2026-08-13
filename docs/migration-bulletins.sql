-- =====================================================================
-- BlaineSide UCP — County Bulletin
--
-- Run once in phpMyAdmin (database: blainefucp). One new table; nothing
-- existing is touched.
--
-- The bulletin page and the dashboard carousel both read from here, so
-- what staff publish is what players see — no more editing an array in
-- dashboard/index.html to change the news.
-- =====================================================================


CREATE TABLE IF NOT EXISTS ucp_bulletins (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Sets the tag and the colour wash behind the slide.
  type          ENUM('event','update','notice') NOT NULL DEFAULT 'notice',

  -- Lengths match the editor's own counters, so the form can't produce
  -- something the column then truncates.
  title         VARCHAR(70)  NOT NULL,
  body          VARCHAR(240) NOT NULL,

  -- Optional "Read more" target. Whole slide becomes clickable when set.
  link          VARCHAR(500) DEFAULT NULL,

  -- The banner image, stored as a data: URL.
  --
  -- Not a file on disk, deliberately: uploads need a writable directory,
  -- permissions that survive an FTP deploy, and a cleanup story for
  -- orphans. The editor downscales to 1600px wide and re-encodes as JPEG
  -- before sending, which puts a typical banner around 150-300 KB, and
  -- api/bulletin-save.php refuses anything over 1.2 MB. MEDIUMTEXT holds
  -- 16 MB, so there is a lot of headroom for a table that will only ever
  -- have a few dozen rows.
  image         MEDIUMTEXT   DEFAULT NULL,

  -- Vertical framing of that image, 0-100, set by dragging in the editor.
  image_pos     TINYINT UNSIGNED NOT NULL DEFAULT 50,

  -- Whether it rotates on the dashboard. The 5-at-a-time cap is enforced
  -- in api/bulletin-toggle.php, not here — a limit that lives in the
  -- database can't explain itself to the person who just hit it.
  on_dashboard  TINYINT(1)   NOT NULL DEFAULT 0,

  -- Who wrote it. The id is for staff records; the name is what players
  -- see, and is stored rather than joined so a later UCP rename doesn't
  -- silently rewrite the byline on old posts.
  author_id     INT UNSIGNED DEFAULT NULL,
  author_name   VARCHAR(20)  NOT NULL,

  created_at    INT UNSIGNED NOT NULL,
  updated_at    INT UNSIGNED DEFAULT NULL,

  PRIMARY KEY (id),
  KEY idx_bulletins_dash (on_dashboard, created_at),
  KEY idx_bulletins_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW TABLES LIKE 'ucp_bulletins';
--   SELECT id, type, title, on_dashboard FROM ucp_bulletins;
--
-- The table starts empty, so the dashboard shows "No bulletins yet" until
-- someone at Management or above publishes one.
-- =====================================================================
