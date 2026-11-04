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

    /** "Captain · Entry" → ['Captain', 'Entry']; "Support" → ['Support', '']. Separators: · | / , – */
    public static function splitRole(string $role): array
    {
        $parts = preg_split('/\s*(?:·|\||\/|,|–|—)\s*/u', trim($role), 2) ?: [''];
        return [trim((string) ($parts[0] ?? '')), trim((string) ($parts[1] ?? ''))];
    }

    private static function list(string $csv): array
    {
        return array_values(array_filter(array_map(static fn($v) => mb_strtolower(trim($v)), explode(',', $csv)), 'strlen'));
    }

    /** A stable accent colour per group name (hue from the name). */
    public static function hue(string $name): int
    {
        return crc32(mb_strtolower($name)) % 360;
    }

    /**
     * Members for the "Our Squad" panel, filtered, grouped and limited according to the panel settings.
     * @param array<string,mixed> $s panel settings
     * @return array{groups:array<int,array{name:string,total:int,members:array}>,shown:int,hidden:int}
     */
    public static function panelData(array $s): array
    {
        $sq = trim((string) ($s['squad'] ?? ''));
        $rows = db()->all('SELECT * FROM {roster_members} WHERE is_active = 1' . ($sq !== '' ? ' AND squad = ?' : '') . ' ORDER BY sort_order, nickname LIMIT 200', $sq !== '' ? [$sq] : []);
        $only = self::list((string) ($s['only_roles'] ?? ''));
        $hide = self::list((string) ($s['hide_roles'] ?? ''));
        $order = self::list((string) ($s['role_order'] ?? ''));
        $by = in_array($s['group_by'] ?? 'role', ['role', 'squad', 'none'], true) ? (string) ($s['group_by'] ?? 'role') : 'role';
        $perGroup = max(0, min(50, (int) ($s['max_per_group'] ?? 0)));
        $total = max(0, min(200, (int) ($s['count'] ?? 0)));
        $groups = [];
        foreach ($rows as $m) {
            [$main, $tag] = self::splitRole((string) $m['role']);
            $key = mb_strtolower($main);
            if ($only && !in_array($key, $only, true)) {
                continue;
            }
            if (in_array($key, $hide, true)) {
                continue;
            }
            $m['_main'] = $main;
            $m['_tag'] = $tag;
            $label = $by === 'role' ? ($main !== '' ? $main : t('Members')) : ($by === 'squad' ? ((string) $m['squad'] !== '' ? (string) $m['squad'] : t('Members')) : '');
            $gk = mb_strtolower($label);
            $groups[$gk]['name'] = $label;
            $groups[$gk]['all'][] = $m;
        }
        if ($by === 'role' && $order) {
            uksort($groups, static function ($a, $b) use ($order) {
                $ia = array_search($a, $order, true);
                $ib = array_search($b, $order, true);
                return ($ia === false ? PHP_INT_MAX : $ia) <=> ($ib === false ? PHP_INT_MAX : $ib);
            });
        }
        $out = [];
        $shown = 0;
        $hidden = 0;
        foreach ($groups as $g) {
            $take = $perGroup > 0 ? array_slice($g['all'], 0, $perGroup) : $g['all'];
            if ($total > 0 && $shown + count($take) > $total) {
                $take = array_slice($take, 0, max(0, $total - $shown));
            }
            $hidden += count($g['all']) - count($take);
            $shown += count($take);
            if ($take) {
                $out[] = ['name' => $g['name'], 'total' => count($g['all']), 'members' => $take];
            }
        }
        return ['groups' => $out, 'shown' => $shown, 'hidden' => $hidden];
    }
}
