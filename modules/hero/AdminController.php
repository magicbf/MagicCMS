<?php
declare(strict_types=1);

namespace MagicCMS\Modules\Hero;

use MagicCMS\Core\{Hero, Nav, Settings, Uploads, View};

/** Admin → Hero: banner settings and slides. */
final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'hero', 'title' => $title, 'inner' => render('hero/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        return $this->page(t('Hero banner'), 'admin', ['cfg' => Hero::config(), 'slides' => db()->all('SELECT * FROM {hero_slides} ORDER BY sort_order, id'), 'newsOn' => (bool) db()->val("SELECT 1 FROM {modules} WHERE module_key = 'news' AND enabled = 1")]);
    }

    public function saveSettings(): never
    {
        $cfg = [];
        foreach (Hero::OPTIONS as $k => $opts) {
            $v = (string) input($k);
            $cfg[$k] = array_key_exists($v, $opts) ? $v : Hero::DEFAULTS[$k];
        }
        $cfg['interval'] = max(3, min(30, (int) input('interval', 7)));
        foreach (['enabled', 'autoplay', 'pause', 'arrows', 'dots', 'parallax'] as $b) {
            $cfg[$b] = !empty($_POST[$b]);
        }
        Settings::set('hero.config', $cfg);
        audit('hero.settings');
        flash('success', t('Hero settings saved.'));
        redirect('/admin/hero');
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {hero_slides} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/hero');
        }
        return $this->page($row ? t('Edit slide') : t('New slide'), 'slide', [
            'row' => $row, 'targets' => Nav::targets(), 'pages' => db()->all('SELECT id, title FROM {pages} ORDER BY title'),
        ]);
    }

    private static function target(string $n): array
    {
        $type = in_array(input("btn{$n}_type"), ['module', 'page', 'url'], true) ? (string) input("btn{$n}_type") : 'module';
        $ref = match ($type) {
            'module' => array_key_exists((string) input("btn{$n}_module"), Nav::targets()) ? (string) input("btn{$n}_module") : 'home',
            'page' => (string) (int) input("btn{$n}_page"),
            default => (string) input("btn{$n}_url"),
        };
        if (($type === 'url' && Nav::safeUrl($ref) === null) || ($type === 'page' && !db()->val('SELECT id FROM {pages} WHERE id = ?', [(int) $ref]))) {
            return ['', 'module', ''];
        }
        return [mb_substr((string) input("btn{$n}_label"), 0, 40), $type, mb_substr($ref, 0, 500)];
    }

    public function saveSlide(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {hero_slides} WHERE id = ?', [$id]) : null;
        $back = $id ? '/admin/hero/slide/' . $id : '/admin/hero/slide/new';
        $image = $old['image'] ?? null;
        try {
            if (($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $new = Uploads::picture($_FILES['image'], 'hero', 'hero', 1920, 900, null);
                Uploads::deletePicture('hero', $image);
                $image = $new;
            } elseif (!empty($_POST['remove_image'])) {
                Uploads::deletePicture('hero', $image);
                $image = null;
            }
        } catch (\RuntimeException $e) {
            flash('danger', $e->getMessage());
            redirect($back);
        }
        [$l1, $t1, $r1] = self::target('1');
        [$l2, $t2, $r2] = self::target('2');
        $col = fn(string $k) => is_hex((string) input($k)) && !empty($_POST[$k . '_on']) ? strtolower((string) input($k)) : '';
        $f = [
            !empty($_POST['enabled']) ? 1 : 0, mb_substr((string) input('eyebrow'), 0, 80), mb_substr((string) input('headline'), 0, 140), mb_substr((string) input('subline'), 0, 300),
            $image, max(0, min(90, (int) input('overlay', 45))), in_array(input('align'), ['left', 'center', 'right'], true) ? (string) input('align') : 'left',
            $l1, $t1, $r1, $l2, $t2, $r2, $col('color_from'), $col('color_to'), local_to_utc((string) input('starts_at')), local_to_utc((string) input('ends_at')),
        ];
        $cols = 'enabled=?, eyebrow=?, headline=?, subline=?, image=?, overlay=?, align=?, btn1_label=?, btn1_type=?, btn1_ref=?, btn2_label=?, btn2_type=?, btn2_ref=?, color_from=?, color_to=?, starts_at=?, ends_at=?';
        if ($old) {
            db()->exec("UPDATE {hero_slides} SET $cols WHERE id = ?", [...$f, $id]);
        } else {
            $ord = (int) db()->val('SELECT COALESCE(MAX(sort_order),0)+1 FROM {hero_slides}');
            $id = db()->insert('INSERT INTO {hero_slides} (enabled, eyebrow, headline, subline, image, overlay, align, btn1_label, btn1_type, btn1_ref, btn2_label, btn2_type, btn2_ref, color_from, color_to, starts_at, ends_at, sort_order, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$f, $ord, now()]);
        }
        audit('hero.slide.save', '#' . $id);
        flash('success', t('Slide saved.'));
        redirect('/admin/hero');
    }

    public function deleteSlide(array $p): never
    {
        $s = db()->one('SELECT id, image FROM {hero_slides} WHERE id = ?', [(int) $p['id']]);
        if ($s) {
            Uploads::deletePicture('hero', $s['image']);
            db()->exec('DELETE FROM {hero_slides} WHERE id = ?', [$s['id']]);
            flash('success', t('Slide deleted.'));
        }
        redirect('/admin/hero');
    }

    public function toggleSlide(array $p): never
    {
        db()->exec('UPDATE {hero_slides} SET enabled = 1 - enabled WHERE id = ?', [(int) $p['id']]);
        redirect('/admin/hero');
    }

    public function moveSlide(array $p): never
    {
        $ids = array_map('intval', array_column(db()->all('SELECT id FROM {hero_slides} ORDER BY sort_order, id'), 'id'));
        $pos = array_search((int) $p['id'], $ids, true);
        $swap = $p['dir'] === 'up' ? $pos - 1 : $pos + 1;
        if ($pos !== false && isset($ids[$swap])) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
        }
        foreach ($ids as $n => $id) {
            db()->exec('UPDATE {hero_slides} SET sort_order = ? WHERE id = ?', [$n + 1, $id]);
        }
        redirect('/admin/hero');
    }
}
