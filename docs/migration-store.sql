CREATE TABLE IF NOT EXISTS ucp_store_tickets (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  account_id      INT UNSIGNED NOT NULL,
  subject         VARCHAR(140) NOT NULL,
  order_ref       VARCHAR(40)  DEFAULT NULL,
  status          VARCHAR(10)  NOT NULL DEFAULT 'open',
  replies         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  last_reply_at   INT UNSIGNED DEFAULT NULL,
  last_reply_by   VARCHAR(20)  DEFAULT NULL,
  last_reply_staff TINYINT(1)  NOT NULL DEFAULT 0,
  closed_at       INT UNSIGNED DEFAULT NULL,
  closed_by       INT UNSIGNED DEFAULT NULL,
  closed_by_name  VARCHAR(20)  DEFAULT NULL,
  created_at      INT UNSIGNED NOT NULL,
  updated_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_st_account (account_id, status),
  KEY idx_st_status (status, updated_at),
  CONSTRAINT fk_st_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ucp_store_ticket_messages (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id       INT UNSIGNED NOT NULL,
  author_id       INT UNSIGNED DEFAULT NULL,
  author_name     VARCHAR(20)  NOT NULL,
  author_rank     TINYINT      NOT NULL DEFAULT 0,
  author_is_staff TINYINT(1)   NOT NULL DEFAULT 0,
  body            TEXT         NOT NULL,
  created_at      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_stm_ticket (ticket_id, created_at),
  CONSTRAINT fk_stm_ticket FOREIGN KEY (ticket_id)
    REFERENCES ucp_store_tickets (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
