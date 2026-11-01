<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Partners;

use MagicCMS\Core\{Uploads, View};

/** Admin → Partners: the logos of the scrolling strip above the footer. The strip itself is the "partners" panel. */
final class Controller
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'partners', 'title' => $title, 'inner' => render('partners/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        $panel = db()->one("SELECT id, enabled FROM {panels} WHERE panel_key = 'partners'");
        return $this->page(t('Partners'), 'index', ['rows' => db()->all('SELECT * FROM {partners} ORDER BY sort_order, id'), 'panel' => $panel]);
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {partners} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/partners');
        }
        return $this->page($row ? t('Edit partner') : t('New partner'), 'edit', ['row' => $row]);
    }

    public function save(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {partners} WHERE id = ?', [$id]) : null;
        $back = $id ? '/admin/partners/' . $id : '/admin/partners/new';
        $name = mb_substr((string) input('name'), 0, 80);
        $url = trim((string) input('url'));
        if ($name === '') {
            flash('danger', t('A name is required.'));
            redirect($back);
        }
        if ($url !== '' && !preg_match('#^https?://[^\s<>"]+$#i', $url)) {
            flash('danger', t('The link must start with http:// or https://'));
            redirect($back);
        }
        $logo = $old['logo'] ?? '';
        try {
            if (($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $new = Uploads::picture($_FILES['logo'], 'partners', 'partner', 600, 300);
                Uploads::deletePicture('partners', $logo);
                $logo = $new;
            } elseif ($logo === '') {
                throw new \RuntimeException(t('Please choose a logo image.'));
            }
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect($back);
        }
        $enabled = input('enabled') === '1' ? 1 : 0;
        if ($old) {
            db()->exec('UPDATE {partners} SET name = ?, url = ?, logo = ?, enabled = ?, sort_order = ? WHERE id = ?', [$name, $url, $logo, $enabled, (int) input('sort_order', $old['sort_order']), $id]);
        } else {
            $ord = (int) db()->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {partners}');
            db()->exec('INSERT INTO {partners} (name, url, logo, enabled, sort_order) VALUES (?,?,?,?,?)', [$name, $url, $logo, $enabled, $ord]);
        }
        audit('partners.save', $name);
        flash('success', t('Partner saved.'));
        redirect('/admin/partners');
    }

    public function delete(array $p): never
    {
        $row = db()->one('SELECT * FROM {partners} WHERE id = ?', [(int) $p['id']]);
        if ($row) {
            Uploads::deletePicture('partners', $row['logo']);
            db()->exec('DELETE FROM {partners} WHERE id = ?', [$row['id']]);
            audit('partners.delete', $row['name']);
            flash('success', t('Partner removed.'));
        }
        redirect('/admin/partners');
    }

    public function toggle(array $p): never
    {
        db()->exec('UPDATE {partners} SET enabled = 1 - enabled WHERE id = ?', [(int) $p['id']]);
        redirect('/admin/partners');
    }

    public function move(array $p): never
    {
        $rows = db()->all('SELECT id FROM {partners} ORDER BY sort_order, id');
        $ids = array_map('intval', array_column($rows, 'id'));
        $i = array_search((int) $p['id'], $ids, true);
        $j = $p['dir'] === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $n => $id) {
                db()->exec('UPDATE {partners} SET sort_order = ? WHERE id = ?', [$n + 1, $id]);
            }
        }
        redirect('/admin/partners');
    }
}
