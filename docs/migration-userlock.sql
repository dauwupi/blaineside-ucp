-- =====================================================================
-- BlaineSide UCP — user lock
--
-- Run once in phpMyAdmin. Select the database first, or keep the USE
-- line below and paste the whole thing.
--
-- A locked account can still sign in far enough to be TOLD it is locked
-- — and no further. That is the point: a player who is simply refused
-- assumes the site is broken and opens a ticket about the wrong thing.
-- =====================================================================

USE blainefucp;


-- ---------------------------------------------------------------------
-- 1. 'locked' as an account status
--
-- status already gates everything: api/_account.php requires 'active' on
-- every authenticated request, so the moment an account reads 'locked'
-- every open session on it stops working. Adding a value to the enum is
-- therefore the whole enforcement mechanism — no new checks scattered
-- through the endpoints to forget.
--
-- If your column is VARCHAR rather than ENUM this statement will error
-- harmlessly and can be skipped; nothing else depends on it.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  MODIFY COLUMN status ENUM('pending','active','suspended','locked')
  NOT NULL DEFAULT 'pending';


-- ---------------------------------------------------------------------
-- 2. Who locked it, when, and why
--
-- On the account rather than in a separate table: there is only ever one
-- current lock, and this is what the sign-in page has to read to explain
-- itself. The history of locks and unlocks goes to ucp_security_log,
-- which already exists and is already what an appeal would be judged on.
--
-- lock_reason is shown TO THE PLAYER on the sign-in page, so whoever
-- types it is writing to them, not about them. The UI says so.
-- ---------------------------------------------------------------------
ALTER TABLE ucp_accounts
  ADD COLUMN locked_at      INT UNSIGNED DEFAULT NULL AFTER status,
  ADD COLUMN locked_by      INT UNSIGNED DEFAULT NULL AFTER locked_at,
  ADD COLUMN locked_by_name VARCHAR(20)  DEFAULT NULL AFTER locked_by,
  ADD COLUMN lock_reason    VARCHAR(190) DEFAULT NULL AFTER locked_by_name;


-- ---------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------
--   SHOW COLUMNS FROM ucp_accounts LIKE 'lock%';
--   SHOW COLUMNS FROM ucp_accounts LIKE 'status';
--
--   SELECT username, status, lock_reason, locked_by_name
--     FROM ucp_accounts WHERE status = 'locked';
--
-- Nothing is locked by running this. To undo a lock by hand:
--   UPDATE ucp_accounts
--      SET status='active', locked_at=NULL, locked_by=NULL,
--          locked_by_name=NULL, lock_reason=NULL
--    WHERE username = 'somebody';
-- =====================================================================
