<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Hardened avatar upload: validate → decode → re-encode (strips metadata) → crop → multi-size. */
final class Uploads
{
    public const SIZES = [256, 128, 64, 32];
    private const MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @return string stored file name (e.g. u12_ab34cd.webp); sized variants are <name>_<size>.<ext> */
    public static function avatar(array $file, int $userId): string
    {
        $maxMb = max(1, min(5, (int) setting('upload.avatar_max_mb', 5)));
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException(t('Please choose an image.'));
        }
        if ($err === UPLOAD_ERR_INI_SIZE) { // php.ini upload_max_filesize is smaller than our own limit
            throw new \RuntimeException(t('Image is too large for this server (limit {n}).', ['n' => ini_get('upload_max_filesize')]));
        }
        if ($err === UPLOAD_ERR_FORM_SIZE || ($file['size'] ?? 0) > $maxMb * 1048576) {
            throw new \RuntimeException(t('Image is too large (max {mb} MB).', ['mb' => $maxMb]));
        }
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException(t('Upload failed.'));
        }
        if (!extension_loaded('gd')) {
            throw new \RuntimeException(t('Image processing (GD) is not available on this server.'));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!in_array($mime, self::MIME, true) || !$info || $info[0] < 16 || $info[1] < 16 || $info[0] * $info[1] > 25_000_000) {
            throw new \RuntimeException(t('Unsupported or invalid image (JPG, PNG, GIF or WebP).'));
        }
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if (!$src) {
            throw new \RuntimeException(t('Could not read the image.'));
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file['tmp_name']);
            $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($rot && ($r = imagerotate($src, $rot, 0))) {
                $src = $r;
            }
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);
        $sx = intdiv($w - $side, 2);
        $sy = intdiv($h - $side, 2);

        $webp = function_exists('imagewebp');
        $ext = $webp ? 'webp' : 'jpg';
        $name = 'u' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dir = MCMS_ROOT . '/uploads/avatars';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException(t('Upload folder is not writable.'));
        }
        foreach (self::SIZES as $s) {
            $dst = imagecreatetruecolor($s, $s);
            if ($webp) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 18, 18, 22));
            }
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $s, $s, $side, $side);
            $path = $dir . '/' . preg_replace('/\.(\w+)$/', '_' . $s . '.$1', $name);
            $ok = $webp ? imagewebp($dst, $path, 86) : imagejpeg($dst, $path, 88);
            imagedestroy($dst);
            if (!$ok) {
                self::deleteAvatar($name);
                throw new \RuntimeException(t('Upload folder is not writable.'));
            }
        }
        imagedestroy($src);
        return $name;
    }

    public static function deleteAvatar(?string $name): void
    {
        if (!$name || !preg_match('/^u\d+_[a-f0-9]{12}\.(webp|jpg)$/', $name)) {
            return;
        }
        foreach (self::SIZES as $s) {
            @unlink(MCMS_ROOT . '/uploads/avatars/' . preg_replace('/\.(\w+)$/', '_' . $s . '.$1', $name));
        }
    }
}
