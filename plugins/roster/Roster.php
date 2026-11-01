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
}
