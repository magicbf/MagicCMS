<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Hardened avatar upload: validate → decode → re-encode (strips metadata) → crop → multi-size. */
final class Uploads
{
    public const SIZES = [256, 128, 64, 32];
    public const MIME = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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

    /**
     * Site logo (admin). Raster images are re-encoded to PNG (max 640x200, alpha kept);
     * SVG is accepted only if it contains no scripts, event handlers or external references.
     * @return string stored file name inside uploads/branding/
     */
    public static function logo(array $file): string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException(t('Please choose an image.'));
        }
        if ($err !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1048576 || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException(t('Upload failed or file larger than 2 MB.'));
        }
        $dir = MCMS_ROOT . '/uploads/branding';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException(t('Upload folder is not writable.'));
        }
        $raw = (string) file_get_contents($file['tmp_name']);
        $name = 'logo_' . bin2hex(random_bytes(6));

        if (self::looksLikeSvg($raw)) {
            if (!self::safeSvg($raw)) {
                throw new \RuntimeException(t('This SVG contains scripts or external references and was rejected.'));
            }
            $name .= '.svg';
            file_put_contents($dir . '/' . $name, $raw);
            return $name;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!extension_loaded('gd') || !in_array($mime, self::MIME, true) || !$info || $info[0] * $info[1] > 25_000_000) {
            throw new \RuntimeException(t('Unsupported image (use PNG, JPG, WebP, GIF or a clean SVG).'));
        }
        $src = @imagecreatefromstring($raw);
        if (!$src) {
            throw new \RuntimeException(t('Could not read the image.'));
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, 640 / $w, 200 / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $name .= '.png';
        $ok = imagepng($dst, $dir . '/' . $name, 7);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            throw new \RuntimeException(t('Upload folder is not writable.'));
        }
        return $name;
    }

    public static function deleteLogo(?string $name): void
    {
        if ($name && preg_match('/^logo_[a-f0-9]{12}\.(png|svg)$/', $name)) {
            @unlink(MCMS_ROOT . '/uploads/branding/' . $name);
        }
    }

    private static function looksLikeSvg(string $raw): bool
    {
        return (bool) preg_match('/<svg[\s>]/i', substr($raw, 0, 4096));
    }

    private static function safeSvg(string $raw): bool
    {
        if (preg_match('/<!ENTITY|<!DOCTYPE[^>]*\[/i', $raw)) {
            return false;
        }
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $ok = $doc->loadXML($raw, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if (!$ok || !$doc->documentElement || strtolower($doc->documentElement->localName) !== 'svg') {
            return false;
        }
        foreach ($doc->getElementsByTagName('*') as $el) {
            if (in_array(strtolower($el->localName), ['script', 'foreignobject', 'iframe', 'object', 'embed', 'audio', 'video', 'animate', 'set', 'handler', 'listener'], true)) {
                return false;
            }
            foreach ($el->attributes as $a) {
                $n = strtolower($a->name);
                $v = preg_replace('/[\x00-\x20]+/', '', strtolower($a->value));
                if (str_starts_with($n, 'on')) {
                    return false;
                }
                if (in_array($n, ['href', 'xlink:href', 'src'], true) && !str_starts_with($v, '#') && !str_starts_with($v, 'data:image/png') && !str_starts_with($v, 'data:image/jpeg')) {
                    return false;
                }
                if (str_contains($v, 'javascript:') || (str_contains($v, 'url(') && !preg_match('/url\(#[\w\-]+\)/', $v))) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Generic picture upload for news covers, category images and hero slides.
     * Validates, decodes, optionally centre-crops to an aspect ratio, downsizes to fit $maxW×$maxH and re-encodes (strips metadata).
     * @param string $prefix  file name prefix, e.g. "news" → news_ab12cd34ef56.webp
     * @param float|null $ratio  crop to this width/height ratio (e.g. 16/9) or null to keep the original
     * @return string stored file name inside uploads/<folder>/
     */
    public static function picture(array $file, string $folder, string $prefix, int $maxW, int $maxH, ?float $ratio = null): string
    {
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException(t('Please choose an image.'));
        }
        if ($err === UPLOAD_ERR_INI_SIZE) {
            throw new \RuntimeException(t('Image is too large for this server (limit {n}).', ['n' => ini_get('upload_max_filesize')]));
        }
        if ($err !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1048576 || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException(t('Upload failed or file larger than 5 MB.'));
        }
        if (!extension_loaded('gd')) {
            throw new \RuntimeException(t('Image processing (GD) is not available on this server.'));
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $info = @getimagesize($file['tmp_name']);
        if (!in_array($mime, self::MIME, true) || !$info || $info[0] < 32 || $info[1] < 32 || $info[0] * $info[1] > 25_000_000) {
            throw new \RuntimeException(t('Unsupported or invalid image (JPG, PNG, GIF or WebP).'));
        }
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if (!$src) {
            throw new \RuntimeException(t('Could not read the image.'));
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $sx = $sy = 0;
        if ($ratio) {
            if ($w / $h > $ratio) { $nw = (int) round($h * $ratio); $sx = intdiv($w - $nw, 2); $w = $nw; }
            else { $nh = (int) round($w / $ratio); $sy = intdiv($h - $nh, 2); $h = $nh; }
        }
        $scale = min(1, $maxW / $w, $maxH / $h);
        $dw = max(1, (int) round($w * $scale));
        $dh = max(1, (int) round($h * $scale));
        $webp = function_exists('imagewebp');
        $dst = imagecreatetruecolor($dw, $dh);
        if ($webp) { imagealphablending($dst, false); imagesavealpha($dst, true); }
        else { imagefill($dst, 0, 0, imagecolorallocate($dst, 18, 18, 22)); }
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $w, $h);
        $dir = MCMS_ROOT . '/uploads/' . $folder;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new \RuntimeException(t('Upload folder is not writable.'));
        }
        $name = $prefix . '_' . bin2hex(random_bytes(6)) . ($webp ? '.webp' : '.jpg');
        $ok = $webp ? imagewebp($dst, $dir . '/' . $name, 84) : imagejpeg($dst, $dir . '/' . $name, 86);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            throw new \RuntimeException(t('Upload folder is not writable.'));
        }
        return $name;
    }

    public static function deletePicture(string $folder, ?string $name): void
    {
        if ($name && preg_match('/^[a-z]+_[a-f0-9]{12}\.(webp|jpg)$/', $name) && preg_match('/^[a-z]+$/', $folder)) {
            @unlink(MCMS_ROOT . '/uploads/' . $folder . '/' . $name);
        }
    }
}
