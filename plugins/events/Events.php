<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Events;

final class Events
{
    /** type => [label, icon, colour] */
    public const TYPES = [
        'event' => ['Event', '🎉', '#ff9a3d'], 'tournament' => ['Tournament', '🏆', '#ff5238'],
        'training' => ['Training', '🎯', '#7cc4ff'], 'stream' => ['Stream', '📺', '#c792ea'],
    ];

    public static function visibleSql(string $a = 'e'): string
    {
        return can('admin.events') ? '1=1' : "$a.status = 'published'";
    }

    public static function counts(int $eventId): array
    {
        $c = ['going' => 0, 'maybe' => 0, 'no' => 0, 'waitlist' => 0];
        foreach (db()->all('SELECT status, COUNT(*) n FROM {event_signups} WHERE event_id = ? GROUP BY status', [$eventId]) as $r) {
            $c[$r['status']] = (int) $r['n'];
        }
        return $c;
    }

    public static function end(array $e): string
    {
        return $e['ends_at'] ?: gmdate('Y-m-d H:i:s', strtotime($e['starts_at'] . ' UTC') + 7200);
    }

    public static function isOver(array $e): bool
    {
        return self::end($e) < now();
    }

    /** @return 'open'|'closed'|'over'|'full' */
    public static function signupState(array $e, array $counts): string
    {
        if (self::isOver($e)) {
            return 'over';
        }
        if (!$e['signup_open'] || ($e['signup_deadline'] && $e['signup_deadline'] < now()) || $e['status'] !== 'published') {
            return 'closed';
        }
        return $e['max_participants'] !== null && $counts['going'] >= (int) $e['max_participants'] ? 'full' : 'open';
    }

    /** Set a member's RSVP. Handles capacity → waitlist and promotes waiting members when a spot frees. */
    public static function rsvp(array $e, int $userId, string $wanted): string
    {
        return db()->transaction(function () use ($e, $userId, $wanted) {
            db()->val('SELECT id FROM {events} WHERE id = ? FOR UPDATE', [$e['id']]); // capacity must not be oversold by parallel requests
            $cur = db()->val('SELECT status FROM {event_signups} WHERE event_id = ? AND user_id = ?', [$e['id'], $userId]);
            $going = (int) db()->val("SELECT COUNT(*) FROM {event_signups} WHERE event_id = ? AND status = 'going'", [$e['id']]);
            $status = $wanted;
            if ($wanted === 'going' && $cur !== 'going') {
                $status = ($e['max_participants'] !== null && $going >= (int) $e['max_participants']) ? 'waitlist' : 'going';
            } elseif ($wanted === 'going' && $cur === 'going') {
                $status = 'going';
            }
            db()->exec(
                'INSERT INTO {event_signups} (event_id, user_id, status, created_at) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status = VALUES(status), created_at = IF(status = VALUES(status), created_at, VALUES(created_at))',
                [$e['id'], $userId, $status, now()]
            );
            if ($cur === 'going' && $status !== 'going') {
                self::promote($e);
            }
            return $status;
        });
    }

    /** Fill free places from the waitlist (oldest first). */
    public static function promote(array $e): void
    {
        if ($e['max_participants'] === null) {
            return;
        }
        $free = (int) $e['max_participants'] - (int) db()->val("SELECT COUNT(*) FROM {event_signups} WHERE event_id = ? AND status = 'going'", [$e['id']]);
        if ($free > 0) {
            db()->exec("UPDATE {event_signups} SET status = 'going' WHERE event_id = ? AND status = 'waitlist' ORDER BY created_at, user_id LIMIT " . $free, [$e['id']]);
        }
    }

    public static function mine(int $eventId): ?string
    {
        return user() ? (db()->val('SELECT status FROM {event_signups} WHERE event_id = ? AND user_id = ?', [$eventId, user()['id']]) ?: null) : null;
    }

    // ------------------------------------------------------------------ iCalendar
    private static function esc(string $s): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $s);
    }

    private static function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 74) {
            $cut = 74;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--; // never split a UTF-8 sequence
            }
            $out .= substr($line, 0, $cut) . "\r\n ";
            $line = substr($line, $cut);
        }
        return $out . $line;
    }

    public static function ics(array $events): string
    {
        $host = parse_url(abs_url(''), PHP_URL_HOST) ?: 'localhost';
        $l = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//MagicCMS//Events//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:' . self::esc((string) setting('site.name', 'MagicCMS'))];
        foreach ($events as $e) {
            $t = fn(string $utc) => gmdate('Ymd\THis\Z', (int) strtotime($utc . ' UTC'));
            $desc = trim(html_entity_decode(strip_tags((string) preg_replace('#<(br|/p)>#i', "\n", (string) $e['description'])), ENT_QUOTES, 'UTF-8'));
            array_push($l, 'BEGIN:VEVENT', 'UID:event-' . (int) $e['id'] . '@' . $host, 'DTSTAMP:' . gmdate('Ymd\THis\Z'), 'DTSTART:' . $t($e['starts_at']), 'DTEND:' . $t(self::end($e)),
                'SUMMARY:' . self::esc($e['title']), 'URL:' . abs_url('events/' . (int) $e['id']));
            if ($desc !== '') {
                $l[] = 'DESCRIPTION:' . self::esc(mb_substr($desc, 0, 600));
            }
            if ($e['location'] !== '') {
                $l[] = 'LOCATION:' . self::esc($e['location']);
            }
            $l[] = 'END:VEVENT';
        }
        $l[] = 'END:VCALENDAR';
        return implode("\r\n", array_map([self::class, 'fold'], $l)) . "\r\n";
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
}
