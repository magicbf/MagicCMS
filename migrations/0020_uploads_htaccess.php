<?php
/** Older installs keep the first uploads/.htaccess, which many hosts answer with "500" for every uploaded image. Replace it with the guarded version. */
return function (MagicCMS\Core\Database $db): void {
    MagicCMS\Core\Uploads::repairHtaccess();
};
