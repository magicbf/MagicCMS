-- Forum attachments, polls and reactions
CREATE TABLE IF NOT EXISTS {forum_attachments} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  orig_name VARCHAR(120) NOT NULL,
  stored VARCHAR(40) NOT NULL,
  thumb VARCHAR(40) NULL,
  mime VARCHAR(60) NOT NULL,
  size INT UNSIGNED NOT NULL DEFAULT 0,
  is_image TINYINT(1) NOT NULL DEFAULT 0,
  downloads INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {forum_polls} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  question VARCHAR(200) NOT NULL,
  multiple TINYINT(1) NOT NULL DEFAULT 0,
  max_choices TINYINT UNSIGNED NOT NULL DEFAULT 1,
  results VARCHAR(12) NOT NULL DEFAULT 'after_vote',
  closes_at DATETIME NULL,
  is_closed TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_topic (topic_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {forum_poll_options} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  poll_id INT UNSIGNED NOT NULL,
  label VARCHAR(100) NOT NULL,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  KEY idx_poll (poll_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {forum_poll_votes} (
  poll_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (option_id, user_id),
  KEY idx_user (poll_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS {forum_reactions} (
  post_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  kind VARCHAR(8) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (post_id, user_id),
  KEY idx_kind (post_id, kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
