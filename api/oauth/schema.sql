-- ============================================================
-- BlaineSide UCP — OAuth2 server tables
-- Run once against the blainefucp database.
-- ============================================================

-- Registered OAuth clients (one row = IPS forum)
CREATE TABLE IF NOT EXISTS ucp_oauth_clients (
  client_id     VARCHAR(80)  NOT NULL,
  client_secret VARCHAR(255) NOT NULL,
  redirect_uri  TEXT         NOT NULL,
  name          VARCHAR(100) NOT NULL DEFAULT '',
  created_at    DATETIME     NOT NULL DEFAULT NOW(),
  PRIMARY KEY (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Short-lived authorisation codes (expire in 10 minutes, single-use)
CREATE TABLE IF NOT EXISTS ucp_oauth_codes (
  code         VARCHAR(128) NOT NULL,
  client_id    VARCHAR(80)  NOT NULL,
  user_id      INT UNSIGNED NOT NULL,
  redirect_uri TEXT         NOT NULL,
  scope        VARCHAR(255) NOT NULL DEFAULT '',
  state        VARCHAR(512) NOT NULL DEFAULT '',
  expires_at   DATETIME     NOT NULL,
  used         TINYINT(1)   NOT NULL DEFAULT 0,
  created_at   DATETIME     NOT NULL DEFAULT NOW(),
  PRIMARY KEY (code),
  KEY idx_user  (user_id),
  KEY idx_exp   (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Access tokens (expire in 1 hour)
CREATE TABLE IF NOT EXISTS ucp_oauth_tokens (
  token      VARCHAR(128) NOT NULL,
  client_id  VARCHAR(80)  NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  scope      VARCHAR(255) NOT NULL DEFAULT '',
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT NOW(),
  PRIMARY KEY (token),
  KEY idx_user (user_id),
  KEY idx_exp  (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Track the IPS forum member ID linked to each UCP account (Option A)
ALTER TABLE ucp_accounts
  ADD COLUMN IF NOT EXISTS forum_member_id INT UNSIGNED NULL DEFAULT NULL
  COMMENT 'IPS member ID created via REST API on registration';

-- Seed the IPS forum OAuth client with the real secret
-- This secret is already configured in IPS ACP (handler id=3)
INSERT INTO ucp_oauth_clients
  (client_id, client_secret, redirect_uri, name)
VALUES
  (
    'ips_forum',
    '99f653aae41cee6080bae3e58200fdf9617e1da3958dadb76052b50b5f0878ed',
    'https://forum.blaineside.com/oauth/callback/',
    'BlaineSide Forum'
  )
ON DUPLICATE KEY UPDATE
  client_secret = VALUES(client_secret),
  redirect_uri  = VALUES(redirect_uri);
