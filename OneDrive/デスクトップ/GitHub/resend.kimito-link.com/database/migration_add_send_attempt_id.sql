-- Migration: Add send_attempt_id and updated_at to send_logs table
-- 実行方法: phpMyAdminでこのSQLを実行

ALTER TABLE send_logs
  ADD COLUMN send_attempt_id CHAR(36) NULL,
  ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- send_attempt_id に UNIQUE 制約を追加（NULLは除外されるので、既存レコードは問題なし）
ALTER TABLE send_logs
  ADD UNIQUE KEY uq_send_attempt (send_attempt_id);

-- 既存レコードの send_attempt_id を NULL のままにしておく（過去ログはそのまま）
