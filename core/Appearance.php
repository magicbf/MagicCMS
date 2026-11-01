<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Light/dark mode + admin-chosen colour scheme.
 * Output is a small <style> block of CSS variables loaded AFTER the theme CSS, so it overrides any theme.
 */
final class Appearance
{
    /** Overridable colour tokens: setting key => [CSS var, label] */
    public const TOKENS = [
        'primary' => ['--c-primary', 'Primary'],
        'primary_hi' => ['--c-primary-hi', 'Highlight'],
        'primary_lo' => ['--c-primary-lo', 'Deep shade'],
        'accent' => ['--c-accent', 'Accent'],
        'on_primary' => ['--c-text-on-primary', 'Text on primary'],
    ];

    public const PRESETS = [
        'crimson' => ['Crimson', ['primary' => '#e5091f', 'primary_hi' => '#ff5238', 'primary_lo' => '#8c0012', 'accent' => '#ff9a3d', 'on_primary' => '#ffffff']],
        'ember' => ['Ember', ['primary' => '#ff2e3b', 'primary_hi' => '#ff8a1f', 'primary_lo' => '#9a1020', 'accent' => '#ffb27a', 'on_primary' => '#ffffff']],
        'royal' => ['Royal', ['primary' => '#2f6bff', 'primary_hi' => '#6e9bff', 'primary_lo' => '#0a2a7a', 'accent' => '#00d4ff', 'on_primary' => '#ffffff']],
        'toxic' => ['Toxic', ['primary' => '#16a34a', 'primary_hi' => '#4ade80', 'primary_lo' => '#064e23', 'accent' => '#bef264', 'on_primary' => '#ffffff']],
        'violet' => ['Violet', ['primary' => '#8b5cf6', 'primary_hi' => '#b794ff', 'primary_lo' => '#3b1a8a', 'accent' => '#f472b6', 'on_primary' => '#ffffff']],
        'mono' => ['Mono', ['primary' => '#d4d4dc', 'primary_hi' => '#ffffff', 'primary_lo' => '#5d5d68', 'accent' => '#a0a0aa', 'on_primary' => '#0b0b0d']],
    ];

    /** The mode setting chosen by site/user/guest: dark | light | auto */
    public static function modeSetting(): string
    {
        $site = (string) setting('appearance.mode_default', 'dark');
        if (!setting('appearance.allow_user_mode', true)) {
            return $site;
        }
        $m = user()['theme_mode'] ?? ($_COOKIE['mcms_mode'] ?? null);
        return in_array($m, ['dark', 'light', 'auto'], true) ? $m : $site;
    }

    /** Initial resolved mode for server-side rendering (JS resolves "auto" before first paint). */
    public static function mode(): string
    {
        $m = self::modeSetting();
        return $m === 'light' ? 'light' : 'dark';
    }

    public static function colors(): array
    {
        $scheme = (string) setting('appearance.scheme', 'crimson');
        if ($scheme === 'custom') {
            $c = (array) setting('appearance.colors', []);
            return array_filter($c, fn($v) => is_string($v) && is_hex($v)) + self::PRESETS['crimson'][1];
        }
        return (self::PRESETS[$scheme] ?? self::PRESETS['crimson'])[1];
    }

    public static function css(): string
    {
        $css = ':root{';
        $colors = self::colors();
        foreach ($colors as $k => $hex) {
            if (isset(self::TOKENS[$k])) {
                $css .= self::TOKENS[$k][0] . ':' . $hex . ';';
            }
        }
        // Text colour for gradient title bars / hero: dark on bright gradients, white on deep ones.
        $lum = (self::luminance($colors['primary_hi']) + self::luminance($colors['primary'])) / 2;
        $dark = $lum > 0.24;
        $css .= '--c-on-bar:' . ($dark ? '#1a0703' : '#ffffff') . ';--bar-text-shadow:' . ($dark ? '0 1px 0 rgba(255,255,255,.35)' : '0 1px 2px rgba(60,0,0,.45)') . ';';
        $r = max(0, min(32, (int) setting('appearance.radius', 16)));
        $css .= '--radius:' . $r . 'px;--radius-sm:' . intdiv($r, 2) . 'px;--radius-lg:' . intdiv($r * 3, 2) . 'px;}';
        return $css;
    }

    public static function motion(): string
    {
        $m = (string) setting('appearance.motion', 'full');
        return in_array($m, ['off', 'subtle', 'full'], true) ? $m : 'full';
    }

    public static function luminance(string $hex): float
    {
        $c = array_map(static function ($v) {
            $v = hexdec($v) / 255;
            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($hex, '#'), 2));
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }
}
