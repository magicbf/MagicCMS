-- Events & tournaments plugin
CREATE TABLE IF NOT EXISTS {events} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(140) NOT NULL,
  type ENUM('event','tournament','training','stream') NOT NULL DEFAULT 'event',
  description MEDIUMTEXT NULL,
  location VARCHAR(160) NOT NULL DEFAULT '',
  url VARCHAR(300) NOT NULL DEFAULT '',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  max_participants SMALLINT UNSIGNED NULL,
  signup_open TINYINT(1) NOT NULL DEFAULT 1,
  signup_deadline DATETIME NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  bracket MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_list (status, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {event_signups} (
  event_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  status ENUM('going','maybe','no','waitlist') NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (event_id, user_id),
  KEY idx_status (event_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
