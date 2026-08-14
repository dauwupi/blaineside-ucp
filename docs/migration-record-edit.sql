USE blainefucp;

ALTER TABLE ucp_punishments
  ADD COLUMN edited_at      INT UNSIGNED DEFAULT NULL,
  ADD COLUMN edited_by      INT UNSIGNED DEFAULT NULL,
  ADD COLUMN edited_by_name VARCHAR(20) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS ucp_punishment_log (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  punishment_id INT UNSIGNED NOT NULL,
  account_id    INT UNSIGNED NOT NULL,
  action        VARCHAR(16) NOT NULL,
  actor_id      INT UNSIGNED DEFAULT NULL,
  actor_name    VARCHAR(20) DEFAULT NULL,
  detail        TEXT DEFAULT NULL,
  snapshot      MEDIUMTEXT DEFAULT NULL,
  created_at    INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_pl_punishment (punishment_id),
  KEY idx_pl_account (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ucp_appeal_punishments
  MODIFY punishment_id INT UNSIGNED DEFAULT NULL;
