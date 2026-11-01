-- Notifications centre + forum subscriptions

CREATE TABLE IF NOT EXISTS {notifications} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(32) NOT NULL,
  ref VARCHAR(48) NOT NULL DEFAULT '',
  title VARCHAR(160) NOT NULL,
  body VARCHAR(300) NOT NULL DEFAULT '',
  url VARCHAR(300) NOT NULL DEFAULT '',
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY idx_user (user_id, read_at, id),
  KEY idx_ref (user_id, ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_topic_subs} (
  user_id INT UNSIGNED NOT NULL,
  topic_id BIGINT UNSIGNED NOT NULL,
  notified_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, topic_id),
  KEY idx_topic (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_board_subs} (
  user_id INT UNSIGNED NOT NULL,
  board_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, board_id),
  KEY idx_board (board_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE {users}
  ADD COLUMN forum_autosub TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN notify_forum TINYINT(1) NOT NULL DEFAULT 1
