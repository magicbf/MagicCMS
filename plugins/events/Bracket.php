<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Events;

/**
 * Single-elimination bracket (2–32 entrants). Pure data functions; the bracket is stored as JSON:
 * {"rounds":[[{"a":"Name"|null,"b":"Name"|null,"sa":int|null,"sb":int|null}, …], …],"size":8}
 * Byes (opponent null) advance automatically; winners propagate whenever scores change.
 */
final class Bracket
{
    public const MAX = 32;

    /** @param string[] $names in seed order (1st = top seed) */
    public static function generate(array $names, bool $shuffle = false): array
    {
        $names = array_values(array_unique(array_filter(array_map(fn($n) => mb_substr(trim((string) $n), 0, 40), $names), 'strlen')));
        $names = array_slice($names, 0, self::MAX);
        $n = count($names);
        if ($n < 2) {
            throw new \InvalidArgumentException('At least 2 participants are needed.');
        }
        if ($shuffle) {
            shuffle($names);
        }
        $size = 1;
        while ($size < $n) {
            $size *= 2;
        }
        $order = [1];
        while (count($order) < $size) { // standard seeding keeps top seeds apart (1v8, 4v5, 2v7, 3v6 …)
            $m = count($order) * 2 + 1;
            $next = [];
            foreach ($order as $s) {
                $next[] = $s;
                $next[] = $m - $s;
            }
            $order = $next;
        }
        $first = [];
        for ($i = 0; $i < $size; $i += 2) {
            $first[] = ['a' => $names[$order[$i] - 1] ?? null, 'b' => $names[$order[$i + 1] - 1] ?? null, 'sa' => null, 'sb' => null];
        }
        $rounds = [$first];
        for ($c = intdiv(count($first), 2); $c >= 1; $c = intdiv($c, 2)) {
            $rounds[] = array_fill(0, $c, ['a' => null, 'b' => null, 'sa' => null, 'sb' => null]);
        }
        return self::recompute(['rounds' => $rounds, 'size' => $size]);
    }

    /** Winner of a match: 'a' | 'b' | null (undecided). */
    public static function winner(array $m): ?string
    {
        if ($m['a'] !== null && $m['b'] === null) {
            return 'a';
        }
        if ($m['b'] !== null && $m['a'] === null) {
            return null; // waiting for the other side (never happens for byes: byes are always on 'b')
        }
        if ($m['a'] === null || $m['sa'] === null || $m['sb'] === null || $m['sa'] === $m['sb']) {
            return null;
        }
        return $m['sa'] > $m['sb'] ? 'a' : 'b';
    }

    /** Propagate winners into later rounds. Scores of matches whose entrants changed are cleared. */
    public static function recompute(array $br): array
    {
        $r =& $br['rounds'];
        for ($ri = 0; $ri < count($r) - 1; $ri++) {
            for ($mi = 0; $mi < count($r[$ri]); $mi++) {
                $w = self::winner($r[$ri][$mi]);
                $name = $w === null ? null : $r[$ri][$mi][$w];
                $slot = $mi % 2 === 0 ? 'a' : 'b';
                $next =& $r[$ri + 1][intdiv($mi, 2)];
                if ($next[$slot] !== $name) {
                    $next[$slot] = $name;
                    $next['sa'] = $next['sb'] = null; // entrants changed → old result is void
                }
                unset($next);
            }
        }
        // byes have no score
        foreach ($r as $ri => $round) {
            foreach ($round as $mi => $m) {
                if ($m['b'] === null || $m['a'] === null) {
                    $r[$ri][$mi]['sa'] = $r[$ri][$mi]['sb'] = null;
                }
            }
        }
        return $br;
    }

    /** Apply submitted scores ($sa[r][i], $sb[r][i]) then recompute. */
    public static function applyScores(array $br, array $sa, array $sb): array
    {
        $orig = $br;
        foreach ($br['rounds'] as $ri => $round) {
            foreach ($round as $mi => $m) {
                if ($m['a'] === null || $m['b'] === null) {
                    continue;
                }
                $now = $br['rounds'][$ri][$mi];
                if ($now['a'] !== $orig['rounds'][$ri][$mi]['a'] || $now['b'] !== $orig['rounds'][$ri][$mi]['b']) {
                    continue; // an earlier result changed who plays here → ignore the stale score
                }
                $x = $sa[$ri][$mi] ?? '';
                $y = $sb[$ri][$mi] ?? '';
                $br['rounds'][$ri][$mi]['sa'] = ($x === '' || !is_numeric($x)) ? null : max(0, min(999, (int) $x));
                $br['rounds'][$ri][$mi]['sb'] = ($y === '' || !is_numeric($y)) ? null : max(0, min(999, (int) $y));
                $br = self::recompute($br); // keep later rounds in sync as we go
            }
        }
        return self::recompute($br);
    }

    public static function champion(array $br): ?string
    {
        $last = end($br['rounds']);
        $m = $last[0] ?? null;
        $w = $m ? self::winner($m) : null;
        return $w ? $m[$w] : null;
    }

    public static function roundName(int $roundIndex, int $totalRounds): string
    {
        $left = $totalRounds - $roundIndex;
        return match ($left) {
            1 => t('Final'), 2 => t('Semifinals'), 3 => t('Quarterfinals'), default => t('Round of {n}', ['n' => 2 ** $left]),
        };
    }

    public static function decode(?string $json): ?array
    {
        $b = $json ? json_decode($json, true) : null;
        return is_array($b) && !empty($b['rounds']) ? $b : null;
    }
}
