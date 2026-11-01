-- Partners: logos shown in the scrolling strip above the footer
CREATE TABLE IF NOT EXISTS {partners} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '',
  logo VARCHAR(64) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE {panels} MODIFY position ENUM('left','right','above_footer') NOT NULL DEFAULT 'left';
