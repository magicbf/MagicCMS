-- Footer builder: panels can be placed in "Footer columns"
ALTER TABLE {panels} MODIFY position ENUM('left','right','above_footer','footer') NOT NULL DEFAULT 'left';
