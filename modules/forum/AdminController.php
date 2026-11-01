<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\{Settings, View};

/** Admin → Forum: categories, subcategories and the forum's side-panel layout. */
final class AdminController
{
    private static function level(int $v): int
    {
        return array_key_exists($v, Forum::LEVELS) ? $v : 0;
    }

    public function index(): string
    {
        $cats = db()->all('SELECT * FROM {forum_categories} ORDER BY sort_order, id');
        $boards = db()->all('SELECT * FROM {forum_boards} ORDER BY sort_order, id');
        $reports = (int) db()->val("SELECT COUNT(*) FROM {forum_reports} WHERE status = 'open'");
        return View::page(t('Forum'), render('admin/_wrap', [
            'tab' => 'forum', 'title' => t('Forum'),
            'inner' => render('forum/admin', ['cats' => $cats, 'boards' => $boards, 'reports' => $reports, 'layout' => Forum::layout()]),
        ]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    /** Which site side-panels stay visible on forum pages (default: none). */
    public function saveLayout(): never
    {
        $l = (string) input('forum_layout');
        Settings::set('forum.layout', array_key_exists($l, Forum::LAYOUTS) ? $l : 'none');
        audit('forum.layout', (string) setting('forum.layout'));
        flash('success', t('Forum layout saved.'));
        redirect('/admin/forum');
    }

    public function saveCategory(): never
    {
        $id = (int) input('id', 0);
        $title = mb_substr((string) input('title'), 0, 120);
        if ($title === '') {
            flash('danger', t('Title is required.'));
            redirect('/admin/forum');
        }
        if ($id && db()->val('SELECT id FROM {forum_categories} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {forum_categories} SET title = ?, sort_order = ? WHERE id = ?', [$title, (int) input('sort_order'), $id]);
        } else {
            db()->exec('INSERT INTO {forum_categories} (title, sort_order) VALUES (?,?)', [$title, (int) input('sort_order', 0)]);
        }
        audit('forum.category.save', $title);
        flash('success', t('Category saved.'));
        redirect('/admin/forum');
    }

    public function deleteCategory(array $p): never
    {
        if (db()->val('SELECT COUNT(*) FROM {forum_boards} WHERE category_id = ?', [(int) $p['id']])) {
            flash('danger', t('Delete or move its subcategories first.'));
        } else {
            db()->exec('DELETE FROM {forum_categories} WHERE id = ?', [(int) $p['id']]);
            audit('forum.category.delete', '#' . (int) $p['id']);
            flash('success', t('Category deleted.'));
        }
        redirect('/admin/forum');
    }

    public function saveSubcategory(): never
    {
        $id = (int) input('id', 0);
        $title = mb_substr((string) input('title'), 0, 120);
        $cat = (int) input('category_id');
        if ($title === '' || !db()->val('SELECT id FROM {forum_categories} WHERE id = ?', [$cat])) {
            flash('danger', t('A title and a category are required.'));
            redirect('/admin/forum');
        }
        $read = self::level((int) input('read_level'));
        $post = max($read, self::level((int) input('post_level', 10)));
        $f = [$cat, $title, mb_substr((string) input('description'), 0, 300), (int) input('sort_order'), $read, $post, empty($_POST['is_locked']) ? 0 : 1];
        if ($id && db()->val('SELECT id FROM {forum_boards} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {forum_boards} SET category_id=?, parent_id=0, title=?, description=?, sort_order=?, read_level=?, post_level=?, is_locked=? WHERE id=?', [...$f, $id]);
        } else {
            db()->exec('INSERT INTO {forum_boards} (category_id, parent_id, title, description, sort_order, read_level, post_level, is_locked) VALUES (?,0,?,?,?,?,?,?)', $f);
        }
        audit('forum.subcategory.save', $title);
        flash('success', t('Subcategory saved.'));
        redirect('/admin/forum');
    }

    public function deleteSubcategory(array $p): never
    {
        $id = (int) $p['id'];
        foreach (db()->all('SELECT id FROM {forum_topics} WHERE board_id = ?', [$id]) as $t) {
            Forum::deleteTopic((int) $t['id']);
        }
        db()->exec('DELETE FROM {forum_boards} WHERE id = ?', [$id]);
        audit('forum.subcategory.delete', '#' . $id);
        flash('success', t('Subcategory and all its topics deleted.'));
        redirect('/admin/forum');
    }
}
