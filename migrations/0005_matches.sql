-- Matches plugin + permission hardening (see 0005_matches.php)

CREATE TABLE IF NOT EXISTS {matches} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  opponent VARCHAR(80) NOT NULL,
  opponent_tag VARCHAR(12) NOT NULL DEFAULT '',
  game VARCHAR(60) NOT NULL DEFAULT '',
  competition VARCHAR(120) NOT NULL DEFAULT '',
  map VARCHAR(80) NOT NULL DEFAULT '',
  status ENUM('upcoming','finished') NOT NULL DEFAULT 'upcoming',
  score_us SMALLINT UNSIGNED NULL,
  score_them SMALLINT UNSIGNED NULL,
  played_at DATETIME NOT NULL,
  vod_url VARCHAR(300) NOT NULL DEFAULT '',
  report MEDIUMTEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_status (status, played_at),
  KEY idx_game (game)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
