<?php
declare(strict_types=1);

namespace MagicCMS\Modules\News;

use MagicCMS\Core\Cover;

/** Shared news logic: visibility, queries, covers, tags, slugs. */
final class News
{
    public const PER_PAGE = 9;

    /** Posts are public when published and their publish date has passed (future date = scheduled). */
    public static function visible(string $a = 'n'): string
    {
        return "$a.status = 'published' AND $a.publish_at <= '" . now() . "'";
    }

    public const SELECT = 'n.*, c.name AS cat_name, c.slug AS cat_slug, c.color AS cat_color, c.icon AS cat_icon, c.image AS cat_image, c.cover_style AS cat_style, u.username AS author';
    public const JOINS = 'LEFT JOIN {news_categories} c ON c.id = n.category_id JOIN {users} u ON u.id = n.author_id';

    /** @return array{rows:array,total:int} */
    public static function list(array $f = [], int $page = 1, int $per = self::PER_PAGE): array
    {
        $where = [self::visible()];
        $params = [];
        $join = '';
        if (!empty($f['category'])) {
            $where[] = 'c.slug = ?';
            $params[] = $f['category'];
        }
        if (!empty($f['tag'])) {
            $join = ' JOIN {news_tag_map} tm ON tm.news_id = n.id JOIN {news_tags} tg ON tg.id = tm.tag_id AND tg.slug = ?';
            array_unshift($params, $f['tag']);
        }
        if (!empty($f['featured'])) {
            $where[] = 'n.featured = 1';
        }
        $w = implode(' AND ', $where);
        $total = (int) db()->val("SELECT COUNT(*) FROM {news} n LEFT JOIN {news_categories} c ON c.id = n.category_id $join WHERE $w", $params);
        $rows = db()->all(
            'SELECT ' . self::SELECT . ' FROM {news} n ' . self::JOINS . " $join WHERE $w ORDER BY n.featured DESC, n.publish_at DESC, n.id DESC LIMIT " . (int) $per . ' OFFSET ' . (max(0, $page - 1) * (int) $per),
            $params
        );
        return ['rows' => $rows, 'total' => $total];
    }

    public static function latest(int $n, ?string $category = null): array
    {
        $where = self::visible() . ($category ? ' AND c.slug = ?' : '');
        return db()->all('SELECT ' . self::SELECT . ' FROM {news} n ' . self::JOINS . " WHERE $where ORDER BY n.publish_at DESC, n.id DESC LIMIT " . max(1, min(30, $n)), $category ? [$category] : []);
    }

    public static function featured(int $n): array
    {
        return db()->all('SELECT ' . self::SELECT . ' FROM {news} n ' . self::JOINS . ' WHERE ' . self::visible() . ' AND n.featured = 1 ORDER BY n.publish_at DESC, n.id DESC LIMIT ' . max(1, min(10, $n)));
    }

    public static function categories(): array
    {
        return db()->all('SELECT c.*, (SELECT COUNT(*) FROM {news} n WHERE n.category_id = c.id AND ' . self::visible() . ') AS posts FROM {news_categories} c ORDER BY c.sort_order, c.name');
    }

    /** Cover image URL: uploaded cover → category picture → generated abstract SVG in the category colour. */
    public static function coverUrl(array $p): string
    {
        if ($u = upload_url('news', $p['cover'] ?? null)) {
            return $u;
        }
        if ($u = upload_url('categories', $p['cat_image'] ?? null)) {
            return $u;
        }
        return url('cover/news/' . (int) $p['id'] . '.svg') . '?v=' . substr(md5(($p['cat_color'] ?? '') . self::style($p)), 0, 6);
    }

    public static function style(array $p): string
    {
        $s = (string) ($p['cat_style'] ?? 'auto');
        if ($s === 'auto' || $s === '') {
            $s = (string) setting('news.cover_style', 'auto');
        }
        return in_array($s, Cover::STYLES, true) ? $s : 'auto';
    }

    public static function catColor(array $p): string
    {
        return is_hex((string) ($p['cat_color'] ?? '')) ? $p['cat_color'] : '#ff5238';
    }

    public static function slugify(string $s): string
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $t = strtolower($t !== false && $t !== '' ? $t : $s);
        return substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', $t), '-'), 0, 90);
    }

    public static function uniqueSlug(string $table, string $base, int $ignoreId = 0): string
    {
        $base = $base !== '' ? $base : 'post';
        $slug = $base;
        for ($i = 2; db()->val("SELECT id FROM {{$table}} WHERE slug = ? AND id <> ?", [$slug, $ignoreId]); $i++) {
            $slug = substr($base, 0, 80) . '-' . $i;
        }
        return $slug;
    }

    /** @return string[] tag names */
    public static function tags(int $newsId): array
    {
        return array_column(db()->all('SELECT t.name, t.slug FROM {news_tag_map} m JOIN {news_tags} t ON t.id = m.tag_id WHERE m.news_id = ? ORDER BY t.name', [$newsId]), null);
    }

    public static function syncTags(int $newsId, string $csv): void
    {
        db()->exec('DELETE FROM {news_tag_map} WHERE news_id = ?', [$newsId]);
        $seen = [];
        foreach (array_slice(explode(',', $csv), 0, 12) as $name) {
            $name = mb_substr(trim($name), 0, 40);
            $slug = self::slugify($name);
            if ($name === '' || $slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $tid = (int) db()->val('SELECT id FROM {news_tags} WHERE slug = ?', [$slug]);
            if (!$tid) {
                $tid = db()->insert('INSERT INTO {news_tags} (name, slug) VALUES (?,?)', [$name, $slug]);
            }
            db()->exec('INSERT IGNORE INTO {news_tag_map} (news_id, tag_id) VALUES (?,?)', [$newsId, $tid]);
        }
    }

    public static function tz(): \DateTimeZone
    {
        $tz = (string) (user()['timezone'] ?? '') ?: (string) setting('site.timezone', 'UTC');
        try {
            return new \DateTimeZone($tz);
        } catch (\Throwable) {
            return new \DateTimeZone('UTC');
        }
    }

    /** "2026-11-01T14:30" typed in the viewer's timezone → UTC DATETIME string (null if empty/invalid). */
    public static function localToUtc(string $local): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}$/', $local)) {
            return null;
        }
        try {
            return (new \DateTimeImmutable(str_replace('T', ' ', $local), self::tz()))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function utcToLocal(?string $utc): string
    {
        return $utc ? (new \DateTimeImmutable($utc, new \DateTimeZone('UTC')))->setTimezone(self::tz())->format('Y-m-d\TH:i') : '';
    }
}
