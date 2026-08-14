USE blainefucp;

ALTER TABLE ucp_appeals
  ADD COLUMN reappeal_at        INT UNSIGNED DEFAULT NULL AFTER concluded_by_name,
  ADD COLUMN overruled_at       INT UNSIGNED DEFAULT NULL AFTER reappeal_at,
  ADD COLUMN overruled_by       INT UNSIGNED DEFAULT NULL AFTER overruled_at,
  ADD COLUMN overruled_by_name  VARCHAR(20)  DEFAULT NULL AFTER overruled_by;

ALTER TABLE ucp_appeals
  ADD KEY idx_app_reappeal (account_id, reappeal_at);

UPDATE ucp_appeals
   SET reappeal_at = concluded_at + 7776000
 WHERE status = 'rejected'
   AND concluded_at IS NOT NULL
   AND reappeal_at IS NULL;
