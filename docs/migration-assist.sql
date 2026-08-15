ALTER TABLE ucp_app_questions
  ADD COLUMN assist       TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN assist_rules TEXT DEFAULT NULL;
