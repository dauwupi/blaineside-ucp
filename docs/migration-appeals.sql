USE blainefucp;

CREATE TABLE IF NOT EXISTS ucp_punishments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id       INT UNSIGNED NOT NULL,
  kind             VARCHAR(24)  NOT NULL,
  permanent        TINYINT(1)   NOT NULL DEFAULT 1,
  expires_at       INT UNSIGNED DEFAULT NULL,
  reason           VARCHAR(255) DEFAULT NULL,
  issued_by        INT UNSIGNED DEFAULT NULL,
  issued_by_name   VARCHAR(20)  DEFAULT NULL,
  issued_at        INT UNSIGNED NOT NULL,
  active           TINYINT(1)   NOT NULL DEFAULT 1,
  lifted_at        INT UNSIGNED DEFAULT NULL,
  lifted_by        INT UNSIGNED DEFAULT NULL,
  lifted_by_name   VARCHAR(20)  DEFAULT NULL,
  lifted_reason    VARCHAR(255) DEFAULT NULL,
  appealable       TINYINT(1)   NOT NULL DEFAULT 1,
  external_ref     VARCHAR(64)  DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_pun_account (account_id, active),
  KEY idx_pun_kind (kind, active),
  CONSTRAINT fk_pun_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_appeals (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id        INT UNSIGNED NOT NULL,
  punishment_id     INT UNSIGNED DEFAULT NULL,
  platforms         VARCHAR(48)  NOT NULL DEFAULT '',
  character_id      INT UNSIGNED DEFAULT NULL,
  body              TEXT         NOT NULL,
  status            VARCHAR(12)  NOT NULL DEFAULT 'pending',
  handler_id        INT UNSIGNED DEFAULT NULL,
  handler_name      VARCHAR(20)  DEFAULT NULL,
  comments_enabled  TINYINT(1)   NOT NULL DEFAULT 1,
  created_at        INT UNSIGNED NOT NULL,
  updated_at        INT UNSIGNED NOT NULL,
  concluded_at      INT UNSIGNED DEFAULT NULL,
  concluded_by      INT UNSIGNED DEFAULT NULL,
  concluded_by_name VARCHAR(20)  DEFAULT NULL,
  reappeal_at       INT UNSIGNED DEFAULT NULL,
  overruled_at      INT UNSIGNED DEFAULT NULL,
  overruled_by      INT UNSIGNED DEFAULT NULL,
  overruled_by_name VARCHAR(20)  DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_app_account (account_id, status),
  KEY idx_app_status (status, created_at),
  KEY idx_app_handler (handler_id, status),
  KEY idx_app_reappeal (account_id, reappeal_at),
  CONSTRAINT fk_app_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_appeal_punishments (
  appeal_id     INT UNSIGNED NOT NULL,
  punishment_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (appeal_id, punishment_id),
  KEY idx_ap_pun (punishment_id),
  CONSTRAINT fk_ap_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE,
  CONSTRAINT fk_ap_punishment FOREIGN KEY (punishment_id)
    REFERENCES ucp_punishments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_appeal_evidence (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id   INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  note        VARCHAR(190) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ev_appeal (appeal_id),
  CONSTRAINT fk_ev_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_appeal_comments (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id       INT UNSIGNED NOT NULL,
  author_id       INT UNSIGNED DEFAULT NULL,
  author_name     VARCHAR(20)  NOT NULL,
  author_is_staff TINYINT(1)   NOT NULL DEFAULT 0,
  staff_only      TINYINT(1)   NOT NULL DEFAULT 0,
  body            TEXT         NOT NULL,
  created_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cm_appeal (appeal_id, created_at),
  CONSTRAINT fk_cm_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_appeal_log (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  appeal_id   INT UNSIGNED NOT NULL,
  actor_id    INT UNSIGNED DEFAULT NULL,
  actor_name  VARCHAR(20)  NOT NULL,
  action      VARCHAR(24)  NOT NULL,
  detail      VARCHAR(255) DEFAULT NULL,
  created_at  INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_lg_appeal (appeal_id, created_at),
  CONSTRAINT fk_lg_appeal FOREIGN KEY (appeal_id)
    REFERENCES ucp_appeals (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ucp_punishments
  (account_id, kind, permanent, reason, issued_by, issued_by_name, issued_at, active)
SELECT a.id, 'user_lock', 1, a.lock_reason, a.locked_by, a.locked_by_name,
       COALESCE(a.locked_at, UNIX_TIMESTAMP()), 1
  FROM ucp_accounts a
 WHERE a.status = 'locked'
   AND NOT EXISTS (
     SELECT 1 FROM ucp_punishments p
      WHERE p.account_id = a.id AND p.kind = 'user_lock' AND p.active = 1
   );
