-- usersテーブルを作成
-- phpMyAdminで besttrust_mail データベースを選択してから実行してください

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(20) NOT NULL,
  provider_sub VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  name VARCHAR(255) NULL,
  picture TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provider_sub (provider, provider_sub),
  UNIQUE KEY uq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
