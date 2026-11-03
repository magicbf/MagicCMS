<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Display roles: cosmetic titles shown next to usernames (Captain, Streamer, Founder …).
 * Independent of the permission roles; a user can hold several. Each role says where it is shown.
 */
final class DisplayRoles
{
    /** place key => label */
    public const PLACES = [
        'forum' => 'Forum posts',
        'shoutbox' => 'Shoutbox',
        'profile' => 'Profile page',
        'members' => 'Members list',
        'comments' => 'News comments',
    ];
    public const STYLES = ['soft' => 'Soft', 'solid' => 'Solid', 'outline' => 'Outline', 'glow' => 'Glow'];

    /** @var array<int,array>|null enabled roles by id, in display order */
    private static ?array $roles = null;
    /** @var array<int,int[]> user id => role ids */
    private static array $by = [];
    /** @var array<int,bool> user ids already loaded */
    private static array $loaded = [];

    /** @return array<int,array> */
    public static function all(): array
    {
        if (self::$roles === null) {
            self::$roles = [];
            try {
                foreach (db()->all('SELECT * FROM {display_roles} WHERE enabled = 1 ORDER BY sort_order, id') as $r) {
                    $r['place_list'] = array_values(array_filter(explode(',', (string) $r['places'])));
                    self::$roles[(int) $r['id']] = $r;
                }
            } catch (\Throwable) {   // table not migrated yet
                self::$roles = [];
            }
        }
        return self::$roles;
    }

    public static function max(): int
    {
        return max(1, min(5, (int) setting('roles.max_shown', 3)));
    }

    /** Load the roles of several users with one query (call before rendering a list). */
    public static function prime(array $userIds): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn($i) => $i > 0 && !isset(self::$loaded[$i]))));
        if (!$ids || !self::all()) {
            foreach ($ids as $i) { self::$loaded[$i] = true; }
            return;
        }
        foreach ($ids as $i) { self::$loaded[$i] = true; self::$by[$i] = []; }
        $in = implode(',', array_fill(0, count($ids), '?'));
        foreach (db()->all("SELECT user_id, role_id FROM {user_display_roles} WHERE user_id IN ($in)", $ids) as $r) {
            self::$by[(int) $r['user_id']][] = (int) $r['role_id'];
        }
    }

    /** @return array<int,array> the roles to show for this user in this place (display order, capped) */
    public static function forUser(int $uid, string $place): array
    {
        if (!isset(self::$loaded[$uid])) {
            self::prime([$uid]);
        }
        $all = self::all();
        $out = [];
        foreach ($all as $id => $r) {   // $all is already in display order
            if (in_array($id, self::$by[$uid] ?? [], true) && in_array($place, $r['place_list'], true)) {
                $out[] = $r;
            }
        }
        return array_slice($out, 0, self::max());
    }

    /** Readable text colour for a filled chip of this colour. */
    public static function textOn(string $hex): string
    {
        return Appearance::luminance($hex) > 0.42 ? '#101010' : '#ffffff';
    }

    public static function chip(array $r): string
    {
        $style = isset(self::STYLES[$r['style']]) ? $r['style'] : 'soft';
        $color = is_hex((string) $r['color']) ? $r['color'] : '#888888';
        return '<span class="drole drole-' . $style . '" style="--rc:' . e($color) . ';--rt:' . self::textOn($color) . '" title="' . e($r['name']) . '">'
            . ($r['icon'] !== '' ? '<i aria-hidden="true">' . e($r['icon']) . '</i>' : '') . e($r['name']) . '</span>';
    }

    /** HTML for one user in one place ('' when there is nothing to show). */
    public static function html(int $uid, string $place): string
    {
        $rows = self::forUser($uid, $place);
        return $rows ? '<span class="droles">' . implode('', array_map([self::class, 'chip'], $rows)) . '</span>' : '';
    }

    /** Plain data for JSON consumers (the shoutbox). */
    public static function data(int $uid, string $place): array
    {
        return array_map(static fn($r) => ['name' => $r['name'], 'icon' => $r['icon'], 'color' => $r['color'], 'style' => $r['style'], 'text' => self::textOn((string) $r['color'])], self::forUser($uid, $place));
    }
}
