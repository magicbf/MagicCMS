-- Forum module: categories → boards (one sub-level) → topics → posts

CREATE TABLE IF NOT EXISTS {forum_categories} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_boards} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(120) NOT NULL,
  description VARCHAR(300) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  read_level SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  post_level SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  is_locked TINYINT(1) NOT NULL DEFAULT 0,
  topic_count INT UNSIGNED NOT NULL DEFAULT 0,
  post_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_cat (category_id, sort_order),
  KEY idx_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_topics} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  board_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(140) NOT NULL,
  type ENUM('normal','sticky','announce') NOT NULL DEFAULT 'normal',
  is_locked TINYINT(1) NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  reply_count INT UNSIGNED NOT NULL DEFAULT 0,
  first_post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_post_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_board (board_id, type, last_post_at),
  KEY idx_last (last_post_at),
  KEY idx_user (user_id),
  FULLTEXT KEY ft_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_posts} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  topic_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL,
  edited_at DATETIME NULL,
  edited_by INT UNSIGNED NULL,
  KEY idx_topic (topic_id, id),
  KEY idx_user (user_id),
  FULLTEXT KEY ft_body (body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_reads} (
  user_id INT UNSIGNED NOT NULL,
  topic_id BIGINT UNSIGNED NOT NULL,
  last_post_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, topic_id),
  KEY idx_topic (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_markread} (
  user_id INT UNSIGNED NOT NULL PRIMARY KEY,
  marked_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {forum_reports} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  post_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  reason VARCHAR(300) NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  handled_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  KEY idx_status (status, created_at),
  KEY idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE {users} ADD COLUMN forum_posts INT UNSIGNED NOT NULL DEFAULT 0
