<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Events;

use MagicCMS\Core\{Nav, Sanitizer, View};

final class AdminController
{
    private function page(string $title, string $view, array $data = []): string
    {
        return View::page($title, render('admin/_wrap', ['tab' => 'events', 'title' => $title, 'inner' => render('events/' . $view, $data)]), ['layout' => 'none', 'class' => 'page-admin']);
    }

    public function index(): string
    {
        $f = in_array(input('f'), ['upcoming', 'past', 'draft'], true) ? (string) input('f') : 'upcoming';
        $cond = match ($f) { 'past' => 'e.starts_at < ?', 'draft' => "e.status = 'draft' AND ? IS NOT NULL", default => 'e.starts_at >= ?' };
        $arg = $f === 'upcoming' ? gmdate('Y-m-d H:i:s', time() - 7200) : now();
        $rows = db()->all("SELECT e.* FROM {events} e WHERE $cond ORDER BY e.starts_at " . ($f === 'past' ? 'DESC' : 'ASC') . ' LIMIT 100', [$arg]);
        foreach ($rows as &$e) {
            $e['counts'] = Events::counts((int) $e['id']);
        }
        return $this->page(t('Events'), 'admin_list', ['rows' => $rows, 'f' => $f]);
    }

    public function edit(array $p = []): string
    {
        $row = isset($p['id']) ? db()->one('SELECT * FROM {events} WHERE id = ?', [(int) $p['id']]) : null;
        if (isset($p['id']) && !$row) {
            redirect('/admin/events');
        }
        $people = $row ? db()->all('SELECT s.status, u.id, u.username FROM {event_signups} s JOIN {users} u ON u.id = s.user_id WHERE s.event_id = ? ORDER BY FIELD(s.status, \'going\', \'waitlist\', \'maybe\', \'no\'), s.created_at', [$row['id']]) : [];
        return $this->page($row ? t('Edit event') : t('New event'), 'admin_edit', ['row' => $row, 'people' => $people, 'bracket' => $row ? Bracket::decode($row['bracket']) : null]);
    }

    public function save(): never
    {
        $id = (int) input('id', 0);
        $old = $id ? db()->one('SELECT * FROM {events} WHERE id = ?', [$id]) : null;
        $back = $id ? '/admin/events/' . $id : '/admin/events/new';
        $title = mb_substr((string) input('title'), 0, 140);
        $start = local_to_utc((string) input('starts_at'));
        $end = local_to_utc((string) input('ends_at'));
        $dead = local_to_utc((string) input('signup_deadline'));
        if ($title === '' || !$start) {
            flash('danger', t('A title and a start date are required.'));
            redirect($back);
        }
        if ($end && $end <= $start) {
            flash('danger', t('The end must be after the start.'));
            redirect($back);
        }
        $url = (string) input('url');
        if ($url !== '' && !preg_match('#^https?://#i', (string) Nav::safeUrl($url))) {
            flash('danger', t('The link must start with http:// or https://'));
            redirect($back);
        }
        $max = input('max_participants') !== '' ? max(1, min(999, (int) input('max_participants'))) : null;
        $f = [
            $title, array_key_exists((string) input('type'), Events::TYPES) ? (string) input('type') : 'event', Sanitizer::html((string) ($_POST['description'] ?? '')) ?: null,
            mb_substr((string) input('location'), 0, 160), mb_substr($url, 0, 300), $start, $end, $max, empty($_POST['signup_open']) ? 0 : 1, $dead,
            input('status') === 'draft' ? 'draft' : 'published',
        ];
        $cols = 'title=?, type=?, description=?, location=?, url=?, starts_at=?, ends_at=?, max_participants=?, signup_open=?, signup_deadline=?, status=?';
        if ($old) {
            db()->exec("UPDATE {events} SET $cols, updated_at=? WHERE id=?", [...$f, now(), $id]);
            Events::promote(['id' => $id, 'max_participants' => $max]); // capacity may have grown
        } else {
            $id = db()->insert('INSERT INTO {events} (title, type, description, location, url, starts_at, ends_at, max_participants, signup_open, signup_deadline, status, created_by, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$f, user()['id'], now(), now()]);
        }
        audit('events.save', $title);
        flash('success', t('Event saved.'));
        redirect('/admin/events/' . $id);
    }

    public function delete(array $p): never
    {
        db()->exec('DELETE FROM {event_signups} WHERE event_id = ?', [(int) $p['id']]);
        db()->exec('DELETE FROM {events} WHERE id = ?', [(int) $p['id']]);
        audit('events.delete', '#' . (int) $p['id']);
        flash('success', t('Event deleted.'));
        redirect('/admin/events');
    }

    public function removeSignup(array $p): never
    {
        $e = db()->one('SELECT * FROM {events} WHERE id = ?', [(int) $p['id']]);
        if ($e) {
            db()->exec('DELETE FROM {event_signups} WHERE event_id = ? AND user_id = ?', [$e['id'], (int) $p['uid']]);
            Events::promote($e);
            flash('success', t('Sign-up removed.'));
        }
        redirect('/admin/events/' . (int) $p['id']);
    }

    /** Bracket actions: generate (from text / sign-ups, optionally shuffled), save scores, reset. */
    public function bracket(array $p): never
    {
        $e = db()->one('SELECT * FROM {events} WHERE id = ?', [(int) $p['id']]);
        if (!$e) {
            redirect('/admin/events');
        }
        $back = '/admin/events/' . (int) $e['id'] . '#bracket';
        $act = (string) input('action');
        $br = Bracket::decode($e['bracket']);
        try {
            if ($act === 'generate') {
                $names = (string) input('source') === 'signups'
                    ? array_column(db()->all("SELECT u.username FROM {event_signups} s JOIN {users} u ON u.id = s.user_id WHERE s.event_id = ? AND s.status = 'going' ORDER BY s.created_at", [$e['id']]), 'username')
                    : (preg_split('/\R/', (string) ($_POST['participants'] ?? '')) ?: []);
                $br = Bracket::generate($names, !empty($_POST['shuffle']));
                flash('success', t('Bracket created for {n} participants.', ['n' => count(array_filter($names, fn($n) => trim((string) $n) !== ''))]));
            } elseif ($act === 'scores' && $br) {
                $br = Bracket::applyScores($br, (array) ($_POST['sa'] ?? []), (array) ($_POST['sb'] ?? []));
                flash('success', t('Results saved.'));
            } elseif ($act === 'reset') {
                $br = null;
                flash('success', t('Bracket removed.'));
            }
        } catch (\InvalidArgumentException) {
            flash('danger', t('At least 2 participants are needed (one per line, max {n}).', ['n' => Bracket::MAX]));
            redirect($back);
        }
        db()->exec('UPDATE {events} SET bracket = ?, updated_at = ? WHERE id = ?', [$br ? json_encode($br, JSON_UNESCAPED_UNICODE) : null, now(), $e['id']]);
        redirect($back);
    }
}
