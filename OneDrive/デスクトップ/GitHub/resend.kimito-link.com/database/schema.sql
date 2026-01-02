-- MailLoop MySQL schema (MVP)

CREATE TABLE IF NOT EXISTS oauth_tokens (
  user_id INT NOT NULL,
  provider VARCHAR(20) NOT NULL,
  access_token_enc TEXT NOT NULL,
  refresh_token_enc TEXT NULL,
  expires_at DATETIME NULL,
  scopes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS message_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_text LONGTEXT NOT NULL,
  attachments_json JSON NULL,
  last_sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_user_updated (user_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recipient_groups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  to_json JSON NULL,
  cc_json JSON NULL,
  bcc_json JSON NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_user_updated (user_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS send_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  template_id INT NOT NULL,
  group_id INT NOT NULL,
  subject_snapshot VARCHAR(255) NOT NULL,
  body_snapshot LONGTEXT NOT NULL,
  attachments_snapshot_json JSON NULL,
  recipient_counts_json JSON NULL,
  status VARCHAR(20) NOT NULL,
  error_json JSON NULL,
  gmail_message_id VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
