-- =====================================================================
-- BlaineSide UCP — County Bulletin: card thumbnails
--
-- Run once in phpMyAdmin (database: blainefucp), after
-- migration-bulletins.sql.
--
-- The management listing shows six cards at a time. Sending the full
-- banner for each — a quarter of a megabyte apiece — to draw a thumbnail
-- the size of a postcard is a slow page for no gain, so a small copy is
-- stored alongside it and that is what the listing carries.
--
-- The editor makes the thumbnail in the browser at the same moment it
-- makes the banner. Rows that predate this column have one generated
-- server-side the first time they are listed, so nothing needs
-- re-uploading.
-- =====================================================================

ALTER TABLE ucp_bulletins
  ADD COLUMN thumb MEDIUMTEXT DEFAULT NULL AFTER image;

-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_bulletins LIKE 'thumb';
