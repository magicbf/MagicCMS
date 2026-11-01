-- Hero slides: optional featured media (rounded rectangular image or YouTube video) beside the text
ALTER TABLE {hero_slides}
  ADD COLUMN media_type ENUM('none','image','youtube') NOT NULL DEFAULT 'none',
  ADD COLUMN media_side ENUM('right','left') NOT NULL DEFAULT 'right',
  ADD COLUMN media_image VARCHAR(64) NULL,
  ADD COLUMN media_youtube VARCHAR(16) NOT NULL DEFAULT ''
