<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Matches;

/** Match helpers: result calculation, record stats, panel size. */
final class Matches
{
    public const PANEL_OPTIONS = [5, 8, 10];
    public const PER_PAGE = 12;

    /** Number of matches shown in the Recent Matches panel (admin setting: 5, 8 or 10). */
    public static function panelCount(): int
    {
        $n = (int) setting('matches.panel_count', 5);
        return in_array($n, self::PANEL_OPTIONS, true) ? $n : 5;
    }

    /** @return 'win'|'loss'|'draw'|null */
    public static function result(array $m): ?string
    {
        if ($m['status'] !== 'finished' || $m['score_us'] === null || $m['score_them'] === null) {
            return null;
        }
        return $m['score_us'] > $m['score_them'] ? 'win' : ($m['score_us'] < $m['score_them'] ? 'loss' : 'draw');
    }

    public static function letter(?string $r): string
    {
        return ['win' => 'W', 'loss' => 'L', 'draw' => 'D'][$r ?? ''] ?? '–';
    }

    /** @return array{played:int,wins:int,losses:int,draws:int,rate:int,streak:string} */
    public static function stats(): array
    {
        $rows = db()->all("SELECT score_us, score_them, status FROM {matches} WHERE status = 'finished' ORDER BY played_at DESC, id DESC");
        $w = $l = $d = 0;
        $streak = '';
        $streakN = 0;
        foreach ($rows as $i => $m) {
            $r = self::result($m);
            $r === 'win' ? $w++ : ($r === 'loss' ? $l++ : $d++);
            if ($i === 0) {
                $streak = $r;
                $streakN = 1;
            } elseif ($streak === $r && $streakN === $i) {
                $streakN++;
            }
        }
        $n = count($rows);
        return ['played' => $n, 'wins' => $w, 'losses' => $l, 'draws' => $d, 'rate' => $n ? (int) round($w / $n * 100) : 0, 'streak' => $streakN ? self::letter($streak) . $streakN : '–'];
    }

    public static function recent(int $limit): array
    {
        return db()->all("SELECT * FROM {matches} WHERE status = 'finished' ORDER BY played_at DESC, id DESC LIMIT " . max(1, min(50, $limit)));
    }

    public static function next(): ?array
    {
        return db()->one("SELECT * FROM {matches} WHERE status = 'upcoming' AND played_at >= ? ORDER BY played_at ASC LIMIT 1", [now()]);
    }
}
