USE blainefucp;

UPDATE ucp_punishments SET kind = 'ban' WHERE kind IN ('game_ban', 'forum_ban', 'discord_ban');

CREATE TABLE IF NOT EXISTS ucp_scratchpad (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id  INT UNSIGNED NOT NULL,
  author_id   INT UNSIGNED DEFAULT NULL,
  author_name VARCHAR(20) DEFAULT NULL,
  author_rank TINYINT UNSIGNED NOT NULL DEFAULT 0,
  body        TEXT NOT NULL,
  created_at  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sp_account (account_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
