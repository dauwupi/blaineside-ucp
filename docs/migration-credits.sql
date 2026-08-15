CREATE TABLE IF NOT EXISTS ucp_credits (
  account_id INT UNSIGNED NOT NULL,
  balance    INT NOT NULL DEFAULT 0,
  updated_at INT UNSIGNED NOT NULL,
  PRIMARY KEY (account_id),
  CONSTRAINT fk_cr_account FOREIGN KEY (account_id)
    REFERENCES ucp_accounts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
