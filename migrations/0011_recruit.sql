-- Recruitment plugin
CREATE TABLE IF NOT EXISTS {recruit_positions} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(80) NOT NULL,
  description VARCHAR(300) NOT NULL DEFAULT '',
  is_open TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {recruit_questions} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  label VARCHAR(200) NOT NULL,
  help VARCHAR(200) NOT NULL DEFAULT '',
  qtype VARCHAR(10) NOT NULL DEFAULT 'text',
  options VARCHAR(500) NOT NULL DEFAULT '',
  required TINYINT(1) NOT NULL DEFAULT 1,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {recruit_applications} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  position_id INT UNSIGNED NULL,
  position_title VARCHAR(80) NOT NULL DEFAULT '',
  name VARCHAR(60) NOT NULL,
  email VARCHAR(190) NOT NULL,
  answers MEDIUMTEXT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'new',
  status_message VARCHAR(500) NOT NULL DEFAULT '',
  token CHAR(32) NOT NULL,
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_token (token),
  KEY idx_status (status, created_at),
  KEY idx_user (user_id),
  KEY idx_email (email),
  KEY idx_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {recruit_notes} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  body VARCHAR(2000) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_app (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
