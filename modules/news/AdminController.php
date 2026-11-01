<?php
declare(strict_types=1);

namespace MagicCMS\Modules\News;

use MagicCMS\Core\{Cover, Sanitizer, Settings, Uploads, View};

/** Admin → News: posts and categories. */
final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'news', 'title' => $title, 'inner' => render('news/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    private static function pick(string $v, array $allowed, string $default): string
    {
        return in_array($v, $allowed, true) ? $v : $default;
    }

    // ------------------------------------------------------------------ posts
    public function index(): string
    {
        $q = (string) input('q', '');
        $st = self::pick((string) input('status', ''), ['draft', 'published', 'scheduled'], '');
        $where = ['1=1'];
        $args = [];
        if ($q !== '') {
            $where[] = 'n.title LIKE ?';
            $args[] = '%' . addcslashes($q, '%_\\') . '%';
        }
        if ($st === 'draft') {
            $where[] = "n.status = 'draft'";
        } elseif ($st === 'published') {
            $where[] = "n.status = 'published' AND n.publish_at <= '" . now() . "'";
        } elseif ($st === 'scheduled') {
            $where[] = "n.status = 'published' AND n.publish_at > '" . now() . "'";
        }
        $w = implode(' AND ', $where);
        $per = 25;
        $total = (int) db()->val("SELECT COUNT(*) FROM {news} n WHERE $w", $args);
        $pages = max(1, (int) ceil($total / $per));
        $page = max(1, min($pages, (int) input('page', 1)));
        $rows = db()->all('SELECT ' . News::SELECT . " FROM {news} n " . News::JOINS . " WHERE $w ORDER BY n.publish_at DESC, n.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
        return $this->page(t('News'), 'admin_list', ['rows' => $rows, 'q' => $q, 'status' => $st, 'page' => $page, 'pages' => $pages, 'total' => $total]);
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT n.*, c.cover_style AS cat_style, c.color AS cat_color, c.image AS cat_image FROM {news} n LEFT JOIN {news_categories} c ON c.id = n.category_id WHERE n.id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/news');
        }
        $tags = $row ? implode(', ', array_column(News::tags((int) $row['id']), 'name')) : '';
        return $this->page($row ? t('Edit post') : t('New post'), 'admin_edit', ['row' => $row, 'cats' => News::categories(), 'tags' => $tags]);
    }

    public function save(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {news} WHERE id = ?', [$id]) : null;
        $title = mb_substr((string) input('title'), 0, 160);
        $back = $id ? '/admin/news/' . $id : '/admin/news/new';
        if ($title === '') {
            flash('danger', t('Title is required.'));
            redirect($back);
        }
        $slug = News::slugify((string) input('slug') ?: $title);
        $slug = News::uniqueSlug('news', $slug, $id);
        $cat = (int) input('category_id');
        $cat = db()->val('SELECT id FROM {news_categories} WHERE id = ?', [$cat]) ? $cat : 0;
        $status = self::pick((string) input('status'), ['draft', 'published'], 'draft');
        $at = News::localToUtc((string) input('publish_at')) ?: ($old['publish_at'] ?? now());
        if ($status === 'published' && !input('publish_at') && !$old) {
            $at = now();
        }
        $body = Sanitizer::html((string) ($_POST['body'] ?? ''));
        $fields = [$cat, $title, $slug, mb_substr((string) input('excerpt'), 0, 400), $body, $status, empty($_POST['featured']) ? 0 : 1, empty($_POST['comments_enabled']) ? 0 : 1, mb_substr((string) input('seo_title'), 0, 160), mb_substr((string) input('seo_desc'), 0, 300), $at];

        $cover = $old['cover'] ?? null;
        try {
            if (($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $new = Uploads::picture($_FILES['cover'], 'news', 'news', 1280, 720, 16 / 9);
                Uploads::deletePicture('news', $cover);
                $cover = $new;
            } elseif (!empty($_POST['remove_cover'])) {
                Uploads::deletePicture('news', $cover);
                $cover = null;
            }
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect($back);
        }

        if ($old) {
            db()->exec('UPDATE {news} SET category_id=?, title=?, slug=?, excerpt=?, body=?, status=?, featured=?, comments_enabled=?, seo_title=?, seo_desc=?, publish_at=?, cover=?, updated_at=? WHERE id=?', [...$fields, $cover, now(), $id]);
        } else {
            $id = db()->insert('INSERT INTO {news} (category_id, title, slug, excerpt, body, status, featured, comments_enabled, seo_title, seo_desc, publish_at, cover, author_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$fields, $cover, user()['id'], now(), now()]);
        }
        News::syncTags($id, (string) input('tags'));
        audit('news.save', $title);
        flash('success', t('Post saved.'));
        redirect('/admin/news/' . $id);
    }

    public function delete(array $p): never
    {
        $row = db()->one('SELECT id, cover, title FROM {news} WHERE id = ?', [(int) $p['id']]);
        if ($row) {
            Uploads::deletePicture('news', $row['cover']);
            db()->exec('DELETE FROM {news_comments} WHERE news_id = ?', [$row['id']]);
            db()->exec('DELETE FROM {news_tag_map} WHERE news_id = ?', [$row['id']]);
            db()->exec('DELETE FROM {news} WHERE id = ?', [$row['id']]);
            audit('news.delete', $row['title']);
            flash('success', t('Post deleted.'));
        }
        redirect('/admin/news');
    }

    // ------------------------------------------------------------------ categories
    public function categories(): string
    {
        return $this->page(t('News categories'), 'admin_cats', ['cats' => News::categories(), 'style' => (string) setting('news.cover_style', 'auto')]);
    }

    public function saveStyle(): never
    {
        Settings::set('news.cover_style', self::pick((string) input('cover_style'), array_merge(['auto'], Cover::STYLES), 'auto'));
        flash('success', t('Default cover style saved.'));
        redirect('/admin/news/categories');
    }

    public function saveCategory(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {news_categories} WHERE id = ?', [$id]) : null;
        $name = mb_substr((string) input('name'), 0, 80);
        if ($name === '') {
            flash('danger', t('Name is required.'));
            redirect('/admin/news/categories');
        }
        $slug = News::uniqueSlug('news_categories', News::slugify((string) input('slug') ?: $name), $id);
        $color = is_hex((string) input('color')) ? strtolower((string) input('color')) : '#ff5238';
        $style = self::pick((string) input('cover_style'), array_merge(['auto'], Cover::STYLES), 'auto');
        $image = $old['image'] ?? null;
        try {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $new = Uploads::picture($_FILES['image'], 'categories', 'cat', 1000, 563, 16 / 9);
                Uploads::deletePicture('categories', $image);
                $image = $new;
            } elseif (!empty($_POST['remove_image'])) {
                Uploads::deletePicture('categories', $image);
                $image = null;
            }
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect('/admin/news/categories');
        }
        $f = [$name, $slug, mb_substr((string) input('description'), 0, 300), $color, mb_substr((string) input('icon'), 0, 8), $image, $style, (int) input('sort_order')];
        if ($old) {
            db()->exec('UPDATE {news_categories} SET name=?, slug=?, description=?, color=?, icon=?, image=?, cover_style=?, sort_order=? WHERE id=?', [...$f, $id]);
        } else {
            db()->exec('INSERT INTO {news_categories} (name, slug, description, color, icon, image, cover_style, sort_order) VALUES (?,?,?,?,?,?,?,?)', $f);
        }
        audit('news.category.save', $name);
        flash('success', t('Category saved.'));
        redirect('/admin/news/categories');
    }

    public function deleteCategory(array $p): never
    {
        $c = db()->one('SELECT id, image FROM {news_categories} WHERE id = ?', [(int) $p['id']]);
        if ($c) {
            Uploads::deletePicture('categories', $c['image']);
            db()->exec('UPDATE {news} SET category_id = 0 WHERE category_id = ?', [$c['id']]);
            db()->exec('DELETE FROM {news_categories} WHERE id = ?', [$c['id']]);
            flash('success', t('Category deleted. Its posts are now uncategorised.'));
        }
        redirect('/admin/news/categories');
    }
}
