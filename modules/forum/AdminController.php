<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Forum;

use MagicCMS\Core\View;

/** Admin → Forum: categories and boards. */
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
            'inner' => render('forum/admin', ['cats' => $cats, 'boards' => $boards, 'reports' => $reports]),
        ]), ['layout' => 'none', 'class' => 'page-admin']);
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
            flash('danger', t('Delete or move its boards first.'));
        } else {
            db()->exec('DELETE FROM {forum_categories} WHERE id = ?', [(int) $p['id']]);
            audit('forum.category.delete', '#' . (int) $p['id']);
            flash('success', t('Category deleted.'));
        }
        redirect('/admin/forum');
    }

    public function saveBoard(): never
    {
        $id = (int) input('id', 0);
        $title = mb_substr((string) input('title'), 0, 120);
        $cat = (int) input('category_id');
        if ($title === '' || !db()->val('SELECT id FROM {forum_categories} WHERE id = ?', [$cat])) {
            flash('danger', t('A title and a category are required.'));
            redirect('/admin/forum');
        }
        $parent = (int) input('parent_id', 0);
        if ($parent && (!db()->val('SELECT id FROM {forum_boards} WHERE id = ? AND parent_id = 0 AND id <> ?', [$parent, $id]))) {
            $parent = 0; // one sub-level only; the parent must be a top-level board
        }
        $read = self::level((int) input('read_level'));
        $post = max($read, self::level((int) input('post_level', 10)));
        $f = [$cat, $parent, $title, mb_substr((string) input('description'), 0, 300), (int) input('sort_order'), $read, $post, empty($_POST['is_locked']) ? 0 : 1];
        if ($id && db()->val('SELECT id FROM {forum_boards} WHERE id = ?', [$id])) {
            db()->exec('UPDATE {forum_boards} SET category_id=?, parent_id=?, title=?, description=?, sort_order=?, read_level=?, post_level=?, is_locked=? WHERE id=?', [...$f, $id]);
        } else {
            db()->exec('INSERT INTO {forum_boards} (category_id, parent_id, title, description, sort_order, read_level, post_level, is_locked) VALUES (?,?,?,?,?,?,?,?)', $f);
        }
        audit('forum.board.save', $title);
        flash('success', t('Board saved.'));
        redirect('/admin/forum');
    }

    public function deleteBoard(array $p): never
    {
        $id = (int) $p['id'];
        if (db()->val('SELECT COUNT(*) FROM {forum_boards} WHERE parent_id = ?', [$id])) {
            flash('danger', t('Delete its sub-boards first.'));
            redirect('/admin/forum');
        }
        foreach (db()->all('SELECT id FROM {forum_topics} WHERE board_id = ?', [$id]) as $t) {
            Forum::deleteTopic((int) $t['id']);
        }
        db()->exec('DELETE FROM {forum_boards} WHERE id = ?', [$id]);
        audit('forum.board.delete', '#' . $id);
        flash('success', t('Board and all its topics deleted.'));
        redirect('/admin/forum');
    }
}
