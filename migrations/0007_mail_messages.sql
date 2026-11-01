-- E-mail verification / password reset tokens, notification preference, private messages

ALTER TABLE {users}
  ADD COLUMN email_verified_at DATETIME NULL,
  ADD COLUMN notify_pm TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS {email_tokens} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('verify','reset','email_change') NOT NULL,
  token_hash CHAR(64) NOT NULL,
  new_email VARCHAR(190) NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_hash (token_hash),
  KEY idx_user (user_id, type),
  FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {pm_threads} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(140) NOT NULL,
  created_by INT UNSIGNED NOT NULL,
  last_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {pm_participants} (
  thread_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  last_read_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  hidden TINYINT(1) NOT NULL DEFAULT 0,
  notified_at DATETIME NULL,
  PRIMARY KEY (thread_id, user_id),
  KEY idx_user (user_id, hidden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {pm_messages} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  thread_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_thread (thread_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {pm_blocks} (
  user_id INT UNSIGNED NOT NULL,
  blocked_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, blocked_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
