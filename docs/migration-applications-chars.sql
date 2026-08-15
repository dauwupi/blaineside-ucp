ALTER TABLE ucp_app_questions
  CHANGE COLUMN min_words min_chars SMALLINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE ucp_app_answers
  CHANGE COLUMN min_words min_chars SMALLINT UNSIGNED NOT NULL DEFAULT 0;

UPDATE ucp_app_questions SET min_chars = min_chars * 6 WHERE min_chars > 0 AND min_chars < 400;

UPDATE ucp_app_answers SET min_chars = min_chars * 6 WHERE min_chars > 0 AND min_chars < 400;
