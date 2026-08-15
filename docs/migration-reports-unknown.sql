USE blainefucp;

ALTER TABLE ucp_reports
  ADD COLUMN unknown      TINYINT(1) NOT NULL DEFAULT 0 AFTER outcome_wanted,
  ADD COLUMN unknown_note TEXT       DEFAULT NULL       AFTER unknown;
