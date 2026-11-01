<?php
declare(strict_types=1);

namespace MagicCMS\Modules\News;

use MagicCMS\Core\{Cover, Router, Sanitizer, View};

final class Controller
{
    private function nf(): string
    {
        (new Router())->notFound();
        return '';
    }

    private function listPage(array $filter, string $title, ?array $cat = null, ?string $tag = null): string
    {
        $page = max(1, (int) input('page', 1));
        $res = News::list($filter, $page);
        $pages = max(1, (int) ceil($res['total'] / News::PER_PAGE));
        $base = url('news' . ($cat ? '/category/' . $cat['slug'] : ($tag ? '/tag/' . $tag : '')));
        return View::page($title, render('news/index', [
            'rows' => $res['rows'], 'cats' => News::categories(), 'cat' => $cat, 'tag' => $tag, 'page' => min($page, $pages), 'pages' => $pages, 'base' => $base,
        ]), ['class' => 'page-news', 'description' => $cat['description'] ?? '']);
    }

    public function index(): string
    {
        return $this->listPage([], t('News'));
    }

    public function category(array $p): string
    {
        $cat = db()->one('SELECT * FROM {news_categories} WHERE slug = ?', [$p['slug']]);
        return $cat ? $this->listPage(['category' => $cat['slug']], $cat['name'], $cat) : $this->nf();
    }

    public function tag(array $p): string
    {
        $tag = db()->one('SELECT * FROM {news_tags} WHERE slug = ?', [$p['slug']]);
        return $tag ? $this->listPage(['tag' => $tag['slug']], '#' . $tag['name'], null, $tag['slug']) : $this->nf();
    }

    public function show(array $p): string
    {
        $post = db()->one('SELECT ' . News::SELECT . ' FROM {news} n ' . News::JOINS . ' WHERE n.slug = ?', [$p['slug']]);
        $preview = $post && can('admin.news');
        if (!$post || (!$preview && !($post['status'] === 'published' && $post['publish_at'] <= now()))) {
            return $this->nf();
        }
        if (empty($_SESSION['nviews'][$post['id']])) {
            $_SESSION['nviews'][$post['id']] = 1;
            db()->exec('UPDATE {news} SET views = views + 1 WHERE id = ?', [$post['id']]);
        }
        $comments = db()->all(
            'SELECT c.*, u.username, u.avatar, r.slug AS role_slug, r.level AS role_level FROM {news_comments} c JOIN {users} u ON u.id = c.user_id JOIN {roles} r ON r.id = u.role_id WHERE c.news_id = ? ORDER BY c.id LIMIT 200',
            [$post['id']]
        );
        $related = db()->all(
            'SELECT ' . News::SELECT . ' FROM {news} n ' . News::JOINS . ' WHERE ' . News::visible() . ' AND n.id <> ? AND n.category_id = ? ORDER BY n.publish_at DESC LIMIT 3',
            [$post['id'], $post['category_id']]
        );
        $cover = News::coverUrl($post);
        $desc = $post['seo_desc'] !== '' ? $post['seo_desc'] : ($post['excerpt'] !== '' ? $post['excerpt'] : mb_substr(trim(strip_tags($post['body'])), 0, 160));
        $og = '<meta property="og:type" content="article"><meta property="og:title" content="' . e($post['seo_title'] ?: $post['title']) . '"><meta property="og:description" content="' . e($desc) . '"><meta property="og:image" content="' . e(str_starts_with($cover, 'http') ? $cover : rtrim(abs_url(''), '/') . $cover) . '">'
            . '<link rel="alternate" type="application/rss+xml" title="' . e(setting('site.name')) . '" href="' . e(url('news/feed.xml')) . '">';
        return View::page($post['seo_title'] !== '' ? $post['seo_title'] : $post['title'], render('news/show', [
            'p' => $post, 'tags' => News::tags((int) $post['id']), 'comments' => $comments, 'related' => $related, 'cover' => $cover, 'preview' => $preview && $post['status'] !== 'published',
        ]), ['class' => 'page-news-article', 'description' => $desc, 'head' => $og]);
    }

    public function comment(array $p): never
    {
        $post = db()->one('SELECT id, slug, comments_enabled FROM {news} n WHERE n.id = ? AND ' . News::visible(), [(int) $p['id']]);
        if (!$post || !$post['comments_enabled'] || !can('news.comment')) {
            flash('danger', t('Comments are not available.'));
            redirect('/news');
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        $last = db()->val('SELECT MAX(created_at) FROM {news_comments} WHERE user_id = ?', [user()['id']]);
        if (mb_strlen($body) < 2 || mb_strlen($body) > 1000) {
            flash('danger', t('A comment must be 2–1000 characters.'));
        } elseif ($last && time() - strtotime($last . ' UTC') < 10) {
            flash('danger', t('Please wait a few seconds before commenting again.'));
        } else {
            db()->exec('INSERT INTO {news_comments} (news_id, user_id, body, created_at) VALUES (?,?,?,?)', [$post['id'], user()['id'], $body, now()]);
            db()->exec('UPDATE {news} SET comment_count = (SELECT COUNT(*) FROM {news_comments} WHERE news_id = ?) WHERE id = ?', [$post['id'], $post['id']]);
            flash('success', t('Comment added.'));
        }
        redirect('/news/' . $post['slug'] . '#comments');
    }

    public function deleteComment(array $p): never
    {
        $c = db()->one('SELECT c.id, c.news_id, c.user_id, n.slug FROM {news_comments} c JOIN {news} n ON n.id = c.news_id WHERE c.id = ?', [(int) $p['id']]);
        if ($c && ((can('news.moderate') && \MagicCMS\Core\Auth::mayAffect((int) $c['user_id'])) || (int) $c['user_id'] === (int) user()['id'])) {
            db()->exec('DELETE FROM {news_comments} WHERE id = ?', [$c['id']]);
            db()->exec('UPDATE {news} SET comment_count = (SELECT COUNT(*) FROM {news_comments} WHERE news_id = ?) WHERE id = ?', [$c['news_id'], $c['news_id']]);
            flash('success', t('Comment deleted.'));
        }
        redirect('/news/' . ($c['slug'] ?? '') . '#comments');
    }

    public function feed(): never
    {
        $items = News::latest(20, input('category') ? (string) input('category') : null);
        header('Content-Type: application/rss+xml; charset=utf-8');
        $x = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel>'
            . '<title>' . $x(setting('site.name')) . ' – ' . $x(t('News')) . '</title><link>' . $x(abs_url('news')) . '</link><description>' . $x(setting('site.description')) . '</description>'
            . '<atom:link href="' . $x(abs_url('news/feed.xml')) . '" rel="self" type="application/rss+xml"/>';
        foreach ($items as $i) {
            $img = News::coverUrl($i);
            echo '<item><title>' . $x($i['title']) . '</title><link>' . $x(abs_url('news/' . $i['slug'])) . '</link><guid isPermaLink="true">' . $x(abs_url('news/' . $i['slug'])) . '</guid>'
                . '<pubDate>' . gmdate(DATE_RSS, (int) strtotime($i['publish_at'] . ' UTC')) . '</pubDate>'
                . ($i['cat_name'] ? '<category>' . $x($i['cat_name']) . '</category>' : '')
                . '<description>' . $x($i['excerpt'] !== '' ? $i['excerpt'] : mb_substr(trim(strip_tags($i['body'])), 0, 300)) . '</description>'
                . '<enclosure url="' . $x(str_starts_with($img, 'http') ? $img : rtrim(abs_url(''), '/') . $img) . '" type="' . (str_ends_with($img, '.svg') || str_contains($img, '.svg?') ? 'image/svg+xml' : 'image/webp') . '" length="0"/></item>';
        }
        echo '</channel></rss>';
        exit;
    }

    /** Generated cover artwork (deterministic, cacheable). */
    public function cover(array $p): never
    {
        if ($p['kind'] === 'news') {
            $row = db()->one('SELECT n.id, c.color AS cat_color, c.cover_style AS cat_style FROM {news} n LEFT JOIN {news_categories} c ON c.id = n.category_id WHERE n.id = ?', [(int) $p['id']]);
        } else {
            $row = db()->one('SELECT id, color AS cat_color, cover_style AS cat_style FROM {news_categories} WHERE id = ?', [(int) $p['id']]);
        }
        if (!$row) {
            http_response_code(404);
            exit;
        }
        $style = News::style($row);
        $seed = (int) $row['id'] * ($p['kind'] === 'cat' ? 7 : 1);
        $style = $style === 'auto' ? Cover::STYLES[$seed % 4] : $style;
        $color = News::catColor($row);
        $etag = '"' . md5($p['kind'] . $seed . $color . $style) . '"';
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=31536000, immutable');
        header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'");
        header('Content-Type: image/svg+xml');
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            http_response_code(304);
            exit;
        }
        echo Cover::svg($seed, $color, $style);
        exit;
    }
}
