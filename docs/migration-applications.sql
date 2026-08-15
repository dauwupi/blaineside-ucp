CREATE TABLE IF NOT EXISTS ucp_app_questions (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(140) NOT NULL,
  prompt          TEXT         NOT NULL,
  min_words       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  pinned          TINYINT(1)   NOT NULL DEFAULT 0,
  retired         TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  asked_count     INT UNSIGNED NOT NULL DEFAULT 0,
  created_by      INT UNSIGNED DEFAULT NULL,
  created_by_name VARCHAR(20)  DEFAULT NULL,
  created_at      INT UNSIGNED NOT NULL,
  updated_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_aq_live (retired, pinned, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_app_templates (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title           VARCHAR(140) NOT NULL,
  body            TEXT         NOT NULL,
  use_for         VARCHAR(8)   NOT NULL DEFAULT 'either',
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  used_count      INT UNSIGNED NOT NULL DEFAULT 0,
  created_by      INT UNSIGNED DEFAULT NULL,
  created_by_name VARCHAR(20)  DEFAULT NULL,
  created_at      INT UNSIGNED NOT NULL,
  updated_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_at_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_app_config (
  name       VARCHAR(40) NOT NULL,
  value      VARCHAR(190) NOT NULL,
  updated_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ucp_app_config (name, value, updated_at)
VALUES ('draw_count', '2', UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE name = name;

CREATE TABLE IF NOT EXISTS ucp_applications (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id       INT UNSIGNED NOT NULL,
  attempt          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status           VARCHAR(10)  NOT NULL DEFAULT 'draft',
  claimed_by       INT UNSIGNED DEFAULT NULL,
  claimed_by_name  VARCHAR(20)  DEFAULT NULL,
  claimed_at       INT UNSIGNED DEFAULT NULL,
  decided_by       INT UNSIGNED DEFAULT NULL,
  decided_by_name  VARCHAR(20)  DEFAULT NULL,
  decided_at       INT UNSIGNED DEFAULT NULL,
  feedback         TEXT         DEFAULT NULL,
  submit_ip        VARBINARY(45) DEFAULT NULL,
  submitted_at     INT UNSIGNED DEFAULT NULL,
  created_at       INT UNSIGNED NOT NULL,
  updated_at       INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ap_account (account_id, status),
  KEY idx_ap_status (status, submitted_at),
  KEY idx_ap_claim (claimed_by, status),
  CONSTRAINT fk_ap_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_app_answers (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id  INT UNSIGNED NOT NULL,
  question_id     INT UNSIGNED DEFAULT NULL,
  question_title  VARCHAR(140) NOT NULL,
  question_prompt TEXT         NOT NULL,
  min_words       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  pinned          TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  body            TEXT         DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_aa (application_id, sort_order),
  KEY idx_aa_question (question_id),
  CONSTRAINT fk_aa_application FOREIGN KEY (application_id)
    REFERENCES ucp_applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_app_log (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id INT UNSIGNED NOT NULL,
  actor_id       INT UNSIGNED DEFAULT NULL,
  actor_name     VARCHAR(20)  NOT NULL,
  action         VARCHAR(24)  NOT NULL,
  detail         VARCHAR(255) DEFAULT NULL,
  created_at     INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_alg_app (application_id, created_at),
  CONSTRAINT fk_alg_application FOREIGN KEY (application_id)
    REFERENCES ucp_applications (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_account_ips (
  account_id INT UNSIGNED NOT NULL,
  ip         VARCHAR(45)  NOT NULL,
  hits       INT UNSIGNED NOT NULL DEFAULT 1,
  first_seen INT UNSIGNED NOT NULL,
  last_seen  INT UNSIGNED NOT NULL,
  PRIMARY KEY (account_id, ip),
  KEY idx_aip_ip (ip, last_seen),
  CONSTRAINT fk_aip_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ucp_app_questions
  (title, prompt, min_words, pinned, retired, sort_order, created_by_name, created_at, updated_at)
SELECT * FROM (SELECT
  'Character background' AS title,
  'Write a general background for the character you want to play. Make sure to also state your character''s flaws. At least two paragraphs are expected.' AS prompt,
  150 AS min_words, 1 AS pinned, 0 AS retired, 1 AS sort_order,
  'System' AS created_by_name, UNIX_TIMESTAMP() AS created_at, UNIX_TIMESTAMP() AS updated_at) AS seed
WHERE NOT EXISTS (SELECT 1 FROM ucp_app_questions);

INSERT INTO ucp_app_questions
  (title, prompt, min_words, pinned, retired, sort_order, created_by_name, created_at, updated_at)
SELECT * FROM (SELECT
  'Server rules & regulations' AS title,
  'List two robbery or mugging limitations, and two forbidden types of roleplay.' AS prompt,
  60 AS min_words, 1 AS pinned, 0 AS retired, 2 AS sort_order,
  'System' AS created_by_name, UNIX_TIMESTAMP() AS created_at, UNIX_TIMESTAMP() AS updated_at) AS seed
WHERE NOT EXISTS (SELECT 1 FROM ucp_app_questions WHERE sort_order = 2);
