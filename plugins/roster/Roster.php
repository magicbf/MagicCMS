<?php
declare(strict_types=1);

namespace MagicCMS\Plugins\Roster;

use MagicCMS\Core\Nav;

final class Roster
{
    /** key => [label, icon, kind] kind: url | text */
    public const SOCIALS = [
        'twitch' => ['Twitch', '📺', 'url'], 'youtube' => ['YouTube', '▶️', 'url'], 'x' => ['X / Twitter', '✖️', 'url'], 'instagram' => ['Instagram', '📷', 'url'],
        'tiktok' => ['TikTok', '🎵', 'url'], 'steam' => ['Steam', '🎮', 'url'], 'website' => ['Website', '🌐', 'url'], 'discord' => ['Discord', '💬', 'text'],
    ];

    public static function flag(string $cc): string
    {
        $cc = strtoupper($cc);
        if (!preg_match('/^[A-Z]{2}$/', $cc)) {
            return '';
        }
        return mb_chr(0x1F1E6 + ord($cc[0]) - 65) . mb_chr(0x1F1E6 + ord($cc[1]) - 65);
    }

    /** @return array<string,string> only valid entries */
    public static function cleanSocials(array $in): array
    {
        $out = [];
        foreach (self::SOCIALS as $k => [, , $kind]) {
            $v = trim((string) ($in[$k] ?? ''));
            if ($v === '') {
                continue;
            }
            if ($kind === 'url') {
                $u = Nav::safeUrl($v);
                if ($u !== null && preg_match('#^https?://#i', $u)) {
                    $out[$k] = mb_substr($u, 0, 200);
                }
            } else {
                $out[$k] = mb_substr(preg_replace('/[<>"\']/', '', $v) ?? '', 0, 60);
            }
        }
        return $out;
    }

    /** @return array<int,array{label:string,value:string}> */
    public static function cleanStats(array $labels, array $values): array
    {
        $out = [];
        foreach (array_slice($labels, 0, 6) as $i => $l) {
            $l = mb_substr(trim((string) $l), 0, 20);
            $v = mb_substr(trim((string) ($values[$i] ?? '')), 0, 20);
            if ($l !== '' && $v !== '') {
                $out[] = ['label' => $l, 'value' => $v];
            }
        }
        return $out;
    }

    public static function decode(array $m): array
    {
        $m['socials_a'] = json_decode((string) $m['socials'], true) ?: [];
        $m['stats_a'] = json_decode((string) $m['stats'], true) ?: [];
        return $m;
    }

    public static function initials(string $nick): string
    {
        return mb_strtoupper(mb_substr($nick, 0, 2));
    }

    /** Role order chosen in Admin → Roster (lower-case names). */
    public static function roleOrder(): array
    {
        return array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim($v)), explode(',', (string) setting('roster.role_order', ''))), 'strlen'));
    }

    /** RGB of the automatic role colour (hsl(h,70%,62%)) – used to pre-fill the colour picker. @return int[] */
    public static function hueRgb(int $h): array
    {
        $s = .70; $l = .62; $c = (1 - abs(2 * $l - 1)) * $s; $x = $c * (1 - abs(fmod($h / 60, 2) - 1)); $m = $l - $c / 2;
        [$r, $g, $b] = match (intdiv($h, 60) % 6) { 0 => [$c, $x, 0], 1 => [$x, $c, 0], 2 => [0, $c, $x], 3 => [0, $x, $c], 4 => [$x, 0, $c], default => [$c, 0, $x] };
        return [(int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255)];
    }

    /** Custom role colours chosen in Admin → Roster → Panel & roles: lower-case role => #rrggbb. @return array<string,string> */
    public static function roleColors(): array
    {
        $a = setting('roster.role_colors', []);
        $out = [];
        foreach (is_array($a) ? $a : [] as $k => $v) {
            if (is_string($v) && preg_match('/^#[0-9a-f]{6}$/i', $v)) {
                $out[mb_strtolower((string) $k)] = strtolower($v);
            }
        }
        return $out;
    }

    /** Custom colour of a role, '' when none is set. */
    public static function roleColor(string $role): string
    {
        return self::roleColors()[mb_strtolower(trim($role))] ?? '';
    }

    /** Panel settings (the roster panel's stored options) – the same record the panel renders from. @return array{row:?array,values:array,schema:array} */
    public static function panelConfig(): array
    {
        $row = db()->one("SELECT * FROM {panels} WHERE panel_key = 'roster_squad' ORDER BY id LIMIT 1");
        $schema = \MagicCMS\Core\Panels::registry()['roster_squad']['meta']['settings'] ?? [];
        $stored = $row ? (json_decode((string) $row['settings'], true) ?: []) : [];
        $vals = [];
        foreach ($schema as $k => $def) {
            $vals[$k] = $stored[$k] ?? ($def['default'] ?? '');
        }
        return ['row' => $row, 'values' => $vals, 'schema' => $schema];
    }

    private static function list(string $csv): array
    {
        return array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim($v)), explode(',', $csv)), 'strlen'));
    }

    /** A stable accent colour per role (hue from the name). */
    public static function hue(string $name): int
    {
        return crc32(mb_strtolower($name)) % 360;
    }

    /** Distinct roles currently used (case-insensitive), in display order. @return string[] */
    public static function roles(): array
    {
        $by = [];
        foreach (self::sort(db()->all("SELECT role, sort_order, nickname FROM {roster_members} WHERE role <> ''")) as $m) {
            $by[mb_strtolower((string) $m['role'])] ??= (string) $m['role'];
        }
        return array_values($by);
    }

    /**
     * Sort members by role (the order from Admin → Roster first, then roles by their first member's position), then sort order, then nickname.
     * @param array<int,array> $rows
     * @return array<int,array>
     */
    public static function sort(array $rows, bool $byName = false): array
    {
        $order = self::roleOrder();
        $first = [];
        foreach ($rows as $m) {
            $k = mb_strtolower(trim((string) $m['role']));
            $first[$k] = min($first[$k] ?? PHP_INT_MAX, (int) $m['sort_order']);
        }
        $rank = static function (array $m) use ($order, $first): array {
            $k = mb_strtolower(trim((string) $m['role']));
            $i = array_search($k, $order, true);
            return $i !== false ? [0, (int) $i, 0] : [($k === '' ? 2 : 1), 0, $first[$k] ?? 0];
        };
        usort($rows, static function ($a, $b) use ($rank) {
            return [...$rank($a), mb_strtolower((string) $a['role']), ($byName ? 0 : (int) $a['sort_order']), mb_strtolower((string) $a['nickname'])]
                <=> [...$rank($b), mb_strtolower((string) $b['role']), ($byName ? 0 : (int) $b['sort_order']), mb_strtolower((string) $b['nickname'])];
        });
        return $rows;
    }

    /** @param array<int,array> $rows decoded or raw members @return array<string,array> role label => members (in role order) */
    public static function groupByRole(array $rows, bool $byName = false): array
    {
        $out = [];
        foreach (self::sort($rows, $byName) as $m) {
            $out[trim((string) $m['role']) !== '' ? trim((string) $m['role']) : t('Team')][] = $m;
        }
        return $out;
    }

    /**
     * Members for the roster panel, filtered, grouped by role and limited according to the panel settings.
     * @param array<string,mixed> $s panel settings
     * @return array{groups:array<int,array{name:string,total:int,members:array}>,shown:int,hidden:int}
     */
    public static function panelData(array $s): array
    {
        $rows = db()->all('SELECT * FROM {roster_members} WHERE is_active = 1 ORDER BY sort_order, nickname LIMIT 200');
        $only = self::list((string) ($s['only_roles'] ?? ''));
        $hide = self::list((string) ($s['hide_roles'] ?? ''));
        $by = ($s['group_by'] ?? 'role') === 'none' ? 'none' : 'role';
        $perGroup = max(0, min(50, (int) ($s['max_per_group'] ?? 0)));
        $total = max(0, min(200, (int) ($s['count'] ?? 0)));
        $keep = [];
        foreach ($rows as $m) {
            $key = mb_strtolower(trim((string) $m['role']));
            if (($only && !in_array($key, $only, true)) || in_array($key, $hide, true)) {
                continue;
            }
            $keep[] = $m;
        }
        $byName = ($s['member_sort'] ?? 'order') === 'name';
        $groups = $by === 'none' ? ['' => self::sort($keep, $byName)] : self::groupByRole($keep, $byName);
        $out = [];
        $shown = 0;
        $hidden = 0;
        foreach ($groups as $name => $all) {
            $take = $perGroup > 0 ? array_slice($all, 0, $perGroup) : $all;
            if ($total > 0 && $shown + count($take) > $total) {
                $take = array_slice($take, 0, max(0, $total - $shown));
            }
            $hidden += count($all) - count($take);
            $shown += count($take);
            if ($take) {
                $out[] = ['name' => (string) $name, 'total' => count($all), 'members' => $take];
            }
        }
        return ['groups' => $out, 'shown' => $shown, 'hidden' => $hidden];
    }
}
