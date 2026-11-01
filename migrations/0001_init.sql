-- MagicCMS 0.1.0 initial schema. {name} placeholders get the table prefix.
-- One statement per block, terminated by ";" at end of line. No semicolons inside strings.

CREATE TABLE IF NOT EXISTS {meta} (
  `key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {settings} (
  `key` VARCHAR(100) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {roles} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(32) NOT NULL UNIQUE,
  name VARCHAR(64) NOT NULL,
  level SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  is_system TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {permissions} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  perm_key VARCHAR(64) NOT NULL UNIQUE,
  label VARCHAR(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {role_permissions} (
  role_id INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES {roles}(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES {permissions}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {users} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(32) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  status ENUM('active','pending','banned') NOT NULL DEFAULT 'active',
  avatar VARCHAR(64) NULL,
  bio VARCHAR(500) NOT NULL DEFAULT '',
  signature VARCHAR(300) NOT NULL DEFAULT '',
  timezone VARCHAR(64) NULL,
  theme_mode ENUM('dark','light','auto') NULL,
  created_at DATETIME NOT NULL,
  last_login DATETIME NULL,
  last_seen DATETIME NULL,
  KEY idx_last_seen (last_seen),
  FOREIGN KEY (role_id) REFERENCES {roles}(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {remember_tokens} (
  selector CHAR(18) NOT NULL PRIMARY KEY,
  validator_hash CHAR(64) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  KEY idx_user (user_id),
  FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {login_attempts} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ip_hash CHAR(64) NOT NULL,
  login VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_ip (ip_hash, created_at),
  KEY idx_login (login, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {modules} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  module_key VARCHAR(48) NOT NULL UNIQUE,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  is_core TINYINT(1) NOT NULL DEFAULT 0,
  version VARCHAR(16) NOT NULL DEFAULT '1.0.0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {panels} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  panel_key VARCHAR(48) NOT NULL,
  title VARCHAR(120) NOT NULL,
  position ENUM('left','right') NOT NULL DEFAULT 'left',
  sort_order INT NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  visibility TEXT NULL,
  settings MEDIUMTEXT NULL,
  KEY idx_pos (position, enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {menus} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  menu_key VARCHAR(32) NOT NULL UNIQUE,
  title VARCHAR(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {menu_items} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  label VARCHAR(80) NOT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '',
  target_type ENUM('module','page','url','action') NOT NULL DEFAULT 'url',
  target_ref VARCHAR(500) NOT NULL DEFAULT '',
  new_tab TINYINT(1) NOT NULL DEFAULT 0,
  visibility TEXT NULL,
  badge VARCHAR(16) NOT NULL DEFAULT '',
  color VARCHAR(7) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_menu (menu_id, sort_order),
  FOREIGN KEY (menu_id) REFERENCES {menus}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {pages} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(160) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  layout VARCHAR(8) NOT NULL DEFAULT '',
  published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {online_sessions} (
  sid_hash CHAR(64) NOT NULL PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  last_seen DATETIME NOT NULL,
  location VARCHAR(190) NOT NULL DEFAULT '',
  KEY idx_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {chat_messages} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(400) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_created (created_at),
  FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {audit_log} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(64) NOT NULL,
  detail VARCHAR(500) NOT NULL DEFAULT '',
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
