-- Display roles: cosmetic titles (Captain, Streamer, Founder …) with a colour, an icon and "show where" switches.
-- They are separate from the permission roles (Member, Moderator, Admin …) and can be combined freely.
CREATE TABLE IF NOT EXISTS {display_roles} (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NOT NULL,
  color CHAR(7) NOT NULL DEFAULT '#7ae24b',
  icon VARCHAR(8) NOT NULL DEFAULT '',
  style VARCHAR(10) NOT NULL DEFAULT 'soft',
  places VARCHAR(120) NOT NULL DEFAULT 'forum,shoutbox,profile',
  sort_order INT NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS {user_display_roles} (
  user_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  KEY idx_role (role_id),
  FOREIGN KEY (user_id) REFERENCES {users}(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES {display_roles}(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
