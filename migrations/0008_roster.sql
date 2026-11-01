-- Roster plugin: team members / players
CREATE TABLE IF NOT EXISTS {roster_members} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  nickname VARCHAR(40) NOT NULL,
  real_name VARCHAR(80) NOT NULL DEFAULT '',
  role VARCHAR(60) NOT NULL DEFAULT '',
  squad VARCHAR(60) NOT NULL DEFAULT '',
  country CHAR(2) NOT NULL DEFAULT '',
  bio TEXT NULL,
  photo VARCHAR(64) NULL,
  joined_on DATE NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  socials TEXT NULL,
  stats TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_list (is_active, squad, sort_order),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
