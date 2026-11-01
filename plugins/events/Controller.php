<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Events;

use MagicCMS\Core\{Router, View};

final class Controller
{
    private const PER_PAGE = 10;

    private function nf(): string
    {
        (new Router())->notFound();
        return '';
    }

    public function index(): string
    {
        $view = in_array(input('view'), ['upcoming', 'past', 'calendar'], true) ? (string) input('view') : 'upcoming';
        $vis = Events::visibleSql();
        $data = ['view' => $view];
        if ($view === 'calendar') {
            $m = preg_match('/^\d{4}-\d{2}$/', (string) input('m')) ? (string) input('m') : (new \DateTimeImmutable('now', Events::tz()))->format('Y-m');
            $first = new \DateTimeImmutable($m . '-01 00:00:00', Events::tz());
            $gridStart = $first->modify('monday this week');
            if ((int) $first->format('N') === 1) {
                $gridStart = $first;
            }
            $gridEnd = $gridStart->modify('+42 days');
            $rows = db()->all("SELECT e.* FROM {events} e WHERE $vis AND e.starts_at >= ? AND e.starts_at < ? ORDER BY e.starts_at", [
                $gridStart->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $gridEnd->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            ]);
            $byDay = [];
            foreach ($rows as $e) {
                $day = (new \DateTimeImmutable($e['starts_at'], new \DateTimeZone('UTC')))->setTimezone(Events::tz())->format('Y-m-d');
                $byDay[$day][] = $e;
            }
            $data += ['month' => $first, 'gridStart' => $gridStart, 'byDay' => $byDay, 'today' => (new \DateTimeImmutable('now', Events::tz()))->format('Y-m-d')];
        } else {
            $past = $view === 'past';
            $cond = $past ? 'e.starts_at < ?' : 'COALESCE(e.ends_at, DATE_ADD(e.starts_at, INTERVAL 2 HOUR)) >= ?';
            $total = (int) db()->val("SELECT COUNT(*) FROM {events} e WHERE $vis AND $cond", [now()]);
            $pages = max(1, (int) ceil($total / self::PER_PAGE));
            $page = max(1, min($pages, (int) input('page', 1)));
            $rows = db()->all("SELECT e.* FROM {events} e WHERE $vis AND $cond ORDER BY e.starts_at " . ($past ? 'DESC' : 'ASC') . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE), [now()]);
            foreach ($rows as &$e) {
                $e['counts'] = Events::counts((int) $e['id']);
            }
            $data += ['rows' => $rows, 'page' => $page, 'pages' => $pages];
        }
        return View::page(t('Events'), render('events/index', $data), ['class' => 'page-events']);
    }

    public function show(array $p): string
    {
        $e = db()->one('SELECT * FROM {events} e WHERE e.id = ? AND ' . Events::visibleSql(), [(int) $p['id']]);
        if (!$e) {
            return $this->nf();
        }
        $counts = Events::counts((int) $e['id']);
        $people = db()->all(
            "SELECT s.status, u.id, u.username, u.avatar FROM {event_signups} s JOIN {users} u ON u.id = s.user_id WHERE s.event_id = ? AND s.status IN ('going','maybe','waitlist') ORDER BY s.created_at LIMIT 200",
            [$e['id']]
        );
        return View::page($e['title'], render('events/show', [
            'e' => $e, 'counts' => $counts, 'people' => $people, 'state' => Events::signupState($e, $counts), 'mine' => Events::mine((int) $e['id']),
            'bracket' => Bracket::decode($e['bracket']),
        ]), ['class' => 'page-events', 'description' => mb_substr(trim(strip_tags((string) $e['description'])), 0, 160)]);
    }

    public function signup(array $p): never
    {
        $e = db()->one('SELECT * FROM {events} e WHERE e.id = ? AND ' . Events::visibleSql(), [(int) $p['id']]);
        $want = (string) input('status');
        if (!$e || !in_array($want, ['going', 'maybe', 'no'], true)) {
            redirect('/events');
        }
        $state = Events::signupState($e, Events::counts((int) $e['id']));
        if (in_array($state, ['closed', 'over'], true)) {
            flash('danger', $state === 'over' ? t('This event is over.') : t('Sign-up is closed for this event.'));
        } else {
            $res = Events::rsvp($e, (int) user()['id'], $want);
            flash($res === 'waitlist' ? 'info' : 'success', match ($res) {
                'going' => t('You are in! See you there.'), 'waitlist' => t('The event is full – you are on the waiting list and move up automatically if a place frees up.'),
                'maybe' => t('Marked as “maybe”.'), default => t('Marked as “not going”.'),
            });
        }
        redirect('/events/' . (int) $e['id']);
    }

    public function ics(array $p): never
    {
        $e = db()->one('SELECT * FROM {events} e WHERE e.id = ? AND ' . Events::visibleSql(), [(int) $p['id']]);
        if (!$e) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="event-' . (int) $e['id'] . '.ics"');
        echo Events::ics([$e]);
        exit;
    }

    public function feed(): never
    {
        $rows = db()->all("SELECT * FROM {events} WHERE status = 'published' AND starts_at >= ? ORDER BY starts_at LIMIT 200", [gmdate('Y-m-d H:i:s', time() - 30 * 86400)]);
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="events.ics"');
        echo Events::ics($rows);
        exit;
    }
}
