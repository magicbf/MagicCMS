<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\Uploads;

/**
 * Post attachments. Images are decoded and re-encoded (metadata stripped, downsized, thumbnail);
 * documents are limited to an allow-list of extensions whose real MIME type must match.
 * Files are stored in uploads/forum/ (web access denied) and only ever served by the controller,
 * which checks the read permission of the subcategory.
 */
final class Attachments
{
    public const DOCS = [
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        '7z' => ['application/x-7z-compressed'],
    ];

    public static function enabled(): bool
    {
        return (bool) setting('forum.attach_enabled', true);
    }

    public static function allowed(): bool
    {
        return self::enabled() && can('forum.attach');
    }

    public static function maxBytes(): int
    {
        return max(1, min(5, (int) setting('forum.attach_mb', 5))) * 1048576;
    }

    public static function maxFiles(): int
    {
        return max(1, min(10, (int) setting('forum.attach_files', 5)));
    }

    public static function docsAllowed(): bool
    {
        return (bool) setting('forum.attach_docs', true);
    }

    public static function dir(): string
    {
        $d = MCMS_ROOT . '/uploads/forum';
        if (!is_dir($d)) {
            @mkdir($d, 0755, true);
        }
        if (!is_file($d . '/.htaccess')) {
            @file_put_contents($d . '/.htaccess', "# Served only through the forum controller\nRequire all denied\n");
        }
        return $d;
    }

    /** Turn PHP's $_FILES['files'] structure into a list of single-file arrays (empty slots dropped). */
    public static function normalize($f): array
    {
        $out = [];
        if (!is_array($f) || !isset($f['name']) || !is_array($f['name'])) {
            return $out;
        }
        foreach ($f['name'] as $i => $_) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => (string) $f['name'][$i], 'tmp_name' => (string) $f['tmp_name'][$i], 'error' => (int) $f['error'][$i], 'size' => (int) $f['size'][$i]];
        }
        return $out;
    }

    public static function cleanName(string $n): string
    {
        $n = basename(str_replace('\\', '/', $n));
        $n = preg_replace('/[^\p{L}\p{N}._() \-]/u', '_', $n) ?? 'file';
        $n = trim(preg_replace('/\.{2,}/', '.', $n) ?? $n, " .");
        return mb_substr($n !== '' ? $n : 'file', -100);
    }

    public static function count(int $postId): int
    {
        return (int) db()->val('SELECT COUNT(*) FROM {forum_attachments} WHERE post_id = ?', [$postId]);
    }

    /** @return string[] error messages (successfully stored files stay) */
    public static function save(int $postId, array $files): array
    {
        $errors = [];
        if (!$files) {
            return $errors;
        }
        if (!self::allowed()) {
            return [t('You cannot attach files.')];
        }
        $used = (int) db()->val('SELECT COALESCE(SUM(size),0) FROM {forum_attachments} WHERE user_id = ?', [user()['id']]);
        $quota = max(10, min(5000, (int) setting('forum.attach_quota_mb', 200))) * 1048576;
        $room = self::maxFiles() - self::count($postId);
        foreach ($files as $f) {
            $label = self::cleanName($f['name']);
            if ($room <= 0) {
                $errors[] = t('{f}: too many attachments (maximum {n} per post).', ['f' => $label, 'n' => self::maxFiles()]);
                continue;
            }
            if ($used + (int) $f['size'] > $quota) {
                $errors[] = $label . ': ' . t('your attachment storage is full ({n} MB) – delete old attachments first.', ['n' => (int) ($quota / 1048576)]);
                continue;
            }
            try {
                $used += (int) $f['size'];
                self::store($postId, $f);
                $room--;
            } catch (\RuntimeException $e) {
                $errors[] = $label . ': ' . $e->getMessage();
            }
        }
        return $errors;
    }

    private static function store(int $postId, array $f): void
    {
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > self::maxBytes()) {
            throw new \RuntimeException(t('file is larger than {n} MB.', ['n' => self::maxBytes() / 1048576]));
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            throw new \RuntimeException(t('upload failed.'));
        }
        $dir = self::dir();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $now = now();
        $orig = self::cleanName($f['name']);
        if (in_array($mime, Uploads::MIME, true)) { // image: re-encode
            $stored = Uploads::picture($f, 'forum', 'att', 1920, 1920);
            $thumb = null;
            try {
                $thumb = Uploads::picture($f, 'forum', 'thm', 480, 480);
            } catch (\RuntimeException) {
            }
            $real = (new \finfo(FILEINFO_MIME_TYPE))->file($dir . '/' . $stored) ?: 'image/webp';
            db()->exec(
                'INSERT INTO {forum_attachments} (post_id, user_id, orig_name, stored, thumb, mime, size, is_image, created_at) VALUES (?,?,?,?,?,?,?,1,?)',
                [$postId, user()['id'], $orig, $stored, $thumb, $real, (int) filesize($dir . '/' . $stored), $now]
            );
            return;
        }
        if (!self::docsAllowed()) {
            throw new \RuntimeException(t('only images can be attached.'));
        }
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!isset(self::DOCS[$ext]) || !in_array($mime, self::DOCS[$ext], true)) {
            throw new \RuntimeException(t('file type not allowed (images, PDF, TXT, ZIP, 7Z).'));
        }
        $stored = 'file_' . bin2hex(random_bytes(6));
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stored)) {
            throw new \RuntimeException(t('upload folder is not writable.'));
        }
        @chmod($dir . '/' . $stored, 0644);
        db()->exec(
            'INSERT INTO {forum_attachments} (post_id, user_id, orig_name, stored, thumb, mime, size, is_image, created_at) VALUES (?,?,?,?,NULL,?,?,0,?)',
            [$postId, user()['id'], $orig, $stored, $mime, (int) $f['size'], $now]
        );
    }

    /** @return array<int,array> post id → attachments */
    public static function forPosts(array $postIds): array
    {
        $postIds = array_map('intval', $postIds);
        if (!$postIds) {
            return [];
        }
        $map = [];
        foreach (db()->all('SELECT * FROM {forum_attachments} WHERE post_id IN (' . implode(',', $postIds) . ') ORDER BY is_image DESC, id') as $a) {
            $map[(int) $a['post_id']][] = $a;
        }
        return $map;
    }

    private static function unlinkFiles(array $a): void
    {
        $dir = self::dir();
        foreach ([$a['stored'] ?? null, $a['thumb'] ?? null] as $n) {
            if ($n && preg_match('/^(att|thm|file)_[a-f0-9]{12}(\.(webp|jpg))?$/', $n)) {
                @unlink($dir . '/' . $n);
            }
        }
    }

    public static function deleteOne(int $id, ?int $postId = null): void
    {
        $a = db()->one('SELECT * FROM {forum_attachments} WHERE id = ?' . ($postId !== null ? ' AND post_id = ' . (int) $postId : ''), [$id]);
        if ($a) {
            self::unlinkFiles($a);
            db()->exec('DELETE FROM {forum_attachments} WHERE id = ?', [$id]);
        }
    }

    public static function deleteForPosts(array $postIds): void
    {
        $postIds = array_map('intval', $postIds);
        if (!$postIds) {
            return;
        }
        $in = implode(',', $postIds);
        foreach (db()->all("SELECT * FROM {forum_attachments} WHERE post_id IN ($in)") as $a) {
            self::unlinkFiles($a);
        }
        db()->exec("DELETE FROM {forum_attachments} WHERE post_id IN ($in)");
    }

    public static function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1) . ' MB' : max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
