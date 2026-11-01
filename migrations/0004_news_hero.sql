-- News (categories, posts, tags, comments) and Hero slides

CREATE TABLE IF NOT EXISTS {news_categories} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(300) NOT NULL DEFAULT '',
  color CHAR(7) NOT NULL DEFAULT '#ff5238',
  icon VARCHAR(8) NOT NULL DEFAULT '',
  image VARCHAR(64) NULL,
  cover_style ENUM('auto','blades','orbits','mesh','waves') NOT NULL DEFAULT 'auto',
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {news} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  author_id INT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  slug VARCHAR(160) NOT NULL UNIQUE,
  excerpt VARCHAR(400) NOT NULL DEFAULT '',
  body MEDIUMTEXT NOT NULL,
  cover VARCHAR(64) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
  comment_count INT UNSIGNED NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  seo_title VARCHAR(160) NOT NULL DEFAULT '',
  seo_desc VARCHAR(300) NOT NULL DEFAULT '',
  publish_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_pub (status, publish_at),
  KEY idx_cat (category_id, publish_at),
  KEY idx_feat (featured, publish_at),
  FULLTEXT KEY ft_news (title, excerpt, body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {news_tags} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {news_tag_map} (
  news_id BIGINT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (news_id, tag_id),
  KEY idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {news_comments} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  news_id BIGINT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body VARCHAR(1000) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_news (news_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {hero_slides} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sort_order INT NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  eyebrow VARCHAR(80) NOT NULL DEFAULT '',
  headline VARCHAR(140) NOT NULL DEFAULT '',
  subline VARCHAR(300) NOT NULL DEFAULT '',
  image VARCHAR(64) NULL,
  overlay TINYINT UNSIGNED NOT NULL DEFAULT 45,
  align ENUM('left','center','right') NOT NULL DEFAULT 'left',
  btn1_label VARCHAR(40) NOT NULL DEFAULT '',
  btn1_type ENUM('module','page','url') NOT NULL DEFAULT 'module',
  btn1_ref VARCHAR(500) NOT NULL DEFAULT '',
  btn2_label VARCHAR(40) NOT NULL DEFAULT '',
  btn2_type ENUM('module','page','url') NOT NULL DEFAULT 'module',
  btn2_ref VARCHAR(500) NOT NULL DEFAULT '',
  color_from CHAR(7) NOT NULL DEFAULT '',
  color_to CHAR(7) NOT NULL DEFAULT '',
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  KEY idx_order (enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
