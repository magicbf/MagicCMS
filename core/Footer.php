<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Footer builder: one JSON setting (footer.config) drives layout style, columns, link groups, social buttons,
 * a scrolling ticker, a live stats strip, a giant wordmark and the top decoration – independent of the theme.
 * Extra content can be added with panels placed in the "Footer columns" position (Admin → Panels).
 */
final class Footer
{
    public const STYLES = [
        'columns' => 'Columns – brand and link columns side by side',
        'centered' => 'Centered – brand, links and socials stacked in the middle',
        'minimal' => 'Minimal – one slim line',
        'poster' => 'Poster – columns plus a giant outlined site name',
    ];
    public const TICKERS = ['none' => 'Off', 'news' => 'Latest news', 'results' => 'Results & next match', 'mixed' => 'News, results and events'];
    public const DECOS = ['default' => 'Theme default', 'aurora' => 'Animated aurora line', 'tape' => 'Diagonal tape', 'none' => 'None'];
    /** key => [label, badge text, brand colour] */
    public const SOCIALS = [
        'discord' => ['Discord', 'DC', '#5865f2'], 'twitch' => ['Twitch', 'TW', '#9146ff'], 'youtube' => ['YouTube', 'YT', '#ff0033'],
        'x' => ['X / Twitter', 'X', '#d9d9d9'], 'instagram' => ['Instagram', 'IG', '#e4405f'], 'tiktok' => ['TikTok', 'TT', '#25f4ee'],
        'steam' => ['Steam', 'ST', '#66c0f4'], 'kick' => ['Kick', 'K', '#53fc18'], 'facebook' => ['Facebook', 'f', '#1877f2'],
        'telegram' => ['Telegram', 'TG', '#26a5e4'], 'github' => ['GitHub', 'GH', '#c9d1d9'], 'email' => ['E-mail', '@', '#ffb347'],
    ];
    public const MAX_GROUPS = 3;
    public const MAX_LINKS = 8;

    public static function defaults(): array
    {
        return [
            'style' => 'columns', 'brand' => true, 'about' => '', 'explore' => true, 'community' => true, 'news' => true,
            'groups' => [], 'socials' => [], 'ticker' => 'none', 'ticker_speed' => 5, 'ticker_dir' => 'right',
            'deco' => 'default', 'wordmark' => false, 'stats' => false, 'totop' => true, 'powered' => true,
            'logo_size' => 0, 'name_size' => 0, 'name_show' => true,
        ];
    }

    public static function config(): array
    {
        $stored = (array) setting('footer.config', []);
        return array_replace(self::defaults(), array_intersect_key($stored, self::defaults()));
    }

    /** Footer logo height in px (0 = automatic). Independent of the header setting. */
    public static function logoSize(): int
    {
        return (int) self::config()['logo_size'];
    }

    public static function nameSize(): int
    {
        return (int) self::config()['name_size'];
    }

    private static function url(string $u, bool $mailOk = false): ?string
    {
        $u = trim($u);
        if ($u === '') {
            return null;
        }
        if ($mailOk && preg_match('/^[^\s<>"\']+@[^\s<>"\']+\.[a-z]{2,}$/i', $u)) {
            return 'mailto:' . $u;
        }
        $s = Nav::safeUrl($u);
        return $s !== null ? mb_substr($s, 0, 300) : null;
    }

    /** Validate the admin form into a clean config array. */
    public static function clean(array $in): array
    {
        $c = self::defaults();
        $c['style'] = isset(self::STYLES[$in['style'] ?? '']) ? (string) $in['style'] : 'columns';
        foreach (['brand', 'explore', 'community', 'news', 'wordmark', 'stats', 'totop', 'powered', 'name_show'] as $k) {
            $c[$k] = !empty($in[$k]);
        }
        $c['logo_size'] = ($v = (int) ($in['logo_size'] ?? 0)) > 0 ? max(24, min(160, $v)) : 0;
        $c['name_size'] = ($v = (int) ($in['name_size'] ?? 0)) > 0 ? max(12, min(60, $v)) : 0;
        $c['about'] = mb_substr(trim((string) ($in['about'] ?? '')), 0, 400);
        $c['ticker'] = isset(self::TICKERS[$in['ticker'] ?? '']) ? (string) $in['ticker'] : 'none';
        $c['ticker_speed'] = max(1, min(10, (int) ($in['ticker_speed'] ?? 5)));
        $c['ticker_dir'] = ($in['ticker_dir'] ?? 'right') === 'left' ? 'left' : 'right';
        $c['deco'] = isset(self::DECOS[$in['deco'] ?? '']) ? (string) $in['deco'] : 'default';
        foreach (self::SOCIALS as $k => $_) {
            if (($u = self::url((string) ($in['social'][$k] ?? ''), $k === 'email')) !== null && (preg_match('#^https?://#i', $u) || ($k === 'email' && str_starts_with($u, 'mailto:')))) {
                $c['socials'][$k] = $u;
            }
        }
        for ($g = 0; $g < self::MAX_GROUPS; $g++) {
            $title = mb_substr(trim((string) ($in['g_title'][$g] ?? '')), 0, 40);
            $links = [];
            foreach (preg_split('/\R/', (string) ($in['g_links'][$g] ?? '')) ?: [] as $line) {
                [$label, $url] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                if ($label !== '' && ($u = self::url($url)) !== null && count($links) < self::MAX_LINKS) {
                    $links[] = [mb_substr($label, 0, 60), $u];
                }
            }
            if ($title !== '' && $links) {
                $c['groups'][] = ['title' => $title, 'links' => $links];
            }
        }
        return $c;
    }

    public static function moduleOn(string $key): bool
    {
        return (bool) db()->val('SELECT 1 FROM {modules} WHERE module_key = ? AND enabled = 1', [$key]);
    }

    /** "in 2 d 4 h" / "in 35 min" */
    public static function until(string $utc): string
    {
        $d = max(0, (int) strtotime($utc . ' UTC') - time());
        return match (true) {
            $d < 3600 => t('in {n} min', ['n' => max(1, intdiv($d, 60))]),
            $d < 86400 => t('in {n} h', ['n' => intdiv($d, 3600)]),
            default => t('in {d} d {h} h', ['d' => intdiv($d, 86400), 'h' => intdiv($d % 86400, 3600)]),
        };
    }

    /** @return array<int,array{0:string,1:string,2:string}> [glyph, text, url] */
    public static function tickerItems(string $mode): array
    {
        $out = [];
        if (in_array($mode, ['news', 'mixed'], true) && self::moduleOn('news')) {
            foreach (\MagicCMS\Modules\News\News::latest(5) as $p) {
                $out[] = ['📰', (string) $p['title'], url('news/' . $p['slug'])];
            }
        }
        if (in_array($mode, ['results', 'mixed'], true) && self::moduleOn('matches')) {
            if ($n = \MagicCMS\Plugins\Matches\Matches::next()) {
                $out[] = ['⏱', t('Next: vs {o} {when}', ['o' => $n['opponent'], 'when' => self::until((string) $n['played_at'])]), url('matches/' . (int) $n['id'])];
            }
            foreach (\MagicCMS\Plugins\Matches\Matches::recent(5) as $m) {
                $out[] = ['⚔️', \MagicCMS\Plugins\Matches\Matches::letter(\MagicCMS\Plugins\Matches\Matches::result($m)) . ' ' . $m['score_us'] . ':' . $m['score_them'] . ' ' . t('vs {o}', ['o' => $m['opponent']]), url('matches/' . (int) $m['id'])];
            }
        }
        if ($mode === 'mixed' && self::moduleOn('events')) {
            foreach (db()->all("SELECT id, title, starts_at FROM {events} WHERE status = 'published' AND starts_at >= ? ORDER BY starts_at LIMIT 3", [now()]) as $e) {
                $out[] = ['🗓️', $e['title'] . ' · ' . self::until((string) $e['starts_at']), url('events/' . (int) $e['id'])];
            }
        }
        return $out;
    }

    /** Live strip: members online, member count, next match. */
    public static function stats(): array
    {
        $win = (int) setting('online.window_min', 5);
        $s = [
            ['online', (int) db()->val('SELECT COUNT(*) FROM {online_sessions} WHERE last_seen > ?', [gmdate('Y-m-d H:i:s', time() - $win * 60)]), t('online now')],
            ['members', (int) db()->val("SELECT COUNT(*) FROM {users} WHERE status = 'active'"), t('members')],
        ];
        if (self::moduleOn('matches') && ($n = \MagicCMS\Plugins\Matches\Matches::next())) {
            $s[] = ['next', t('vs {o}', ['o' => $n['opponent']]), self::until((string) $n['played_at'])];
        }
        return $s;
    }
}
