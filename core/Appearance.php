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
        'hazard' => ['Hazard', ['primary' => '#ffc400', 'primary_hi' => '#ffe45c', 'primary_lo' => '#a67c00', 'accent' => '#ff9f00', 'on_primary' => '#14110a']],
        'aurora' => ['Aurora', ['primary' => '#3d5afe', 'primary_hi' => '#6c8cf5', 'primary_lo' => '#1e36b8', 'accent' => '#ff9a6b', 'on_primary' => '#ffffff']],
        'mint' => ['Mint', ['primary' => '#69dcbe', 'primary_hi' => '#8bf5e2', 'primary_lo' => '#2f8f78', 'accent' => '#8bf5e2', 'on_primary' => '#0a0d10']],
        'camo' => ['Electric green', ['primary' => '#3fa12a', 'primary_hi' => '#7ae24b', 'primary_lo' => '#1f6014', 'accent' => '#c4ff3d', 'on_primary' => '#06130a']],
        'gel' => ['Gel blue', ['primary' => '#1f86e0', 'primary_hi' => '#58d0ff', 'primary_lo' => '#0b3f86', 'accent' => '#ff7a2b', 'on_primary' => '#ffffff']],
        'nebula' => ['Nebula', ['primary' => '#7b4dff', 'primary_hi' => '#ff2fb9', 'primary_lo' => '#3b1c9e', 'accent' => '#3aa0ff', 'on_primary' => '#ffffff']],
        'crystal' => ['Crystal', ['primary' => '#a0a8b8', 'primary_hi' => '#e8edf5', 'primary_lo' => '#5a6270', 'accent' => '#7eb8da', 'on_primary' => '#0e1018']],
        'mono' => ['Mono', ['primary' => '#d4d4dc', 'primary_hi' => '#ffffff', 'primary_lo' => '#5d5d68', 'accent' => '#a0a0aa', 'on_primary' => '#0b0b0d']],
    ];

    /**
     * Themes may ship preferred defaults in theme.json: "defaults": {"scheme": "hazard", "radius": 8}.
     * Applied when the theme is activated (installer or Admin → Appearance). @return string[] what was changed
     */
    public static function applyThemeDefaults(array $meta): array
    {
        $changed = [];
        $d = (array) ($meta['defaults'] ?? []);
        if (($meta['supports']['accountInHeader'] ?? true) === false) { // theme without login/register in its header
            db()->exec("UPDATE {panels} SET enabled = 1 WHERE panel_key = 'user_box'");
            $changed[] = t('Account panel switched on (this theme has no account area in the header)');
        }
        if (isset($d['scheme']) && isset(self::PRESETS[$d['scheme']])) {
            Settings::set('appearance.scheme', $d['scheme']);
            $changed[] = t('colour scheme “{n}”', ['n' => self::PRESETS[$d['scheme']][0]]);
        }
        foreach (['header' => Layout::HEADERS, 'rhythm' => Layout::RHYTHMS] as $k => $opts) {
            if (isset($d[$k]) && isset($opts[$d[$k]])) {
                Settings::set('layout.' . $k, $d[$k]);
                $changed[] = t('{what} “{n}”', ['what' => $k === 'header' ? t('header style') : t('layout rhythm'), 'n' => $d[$k]]);
            }
        }
        if (isset($d['mode']) && in_array($d['mode'], ['dark', 'light', 'auto'], true)) {
            Settings::set('appearance.mode_default', $d['mode']);
            $changed[] = t('default mode “{n}”', ['n' => $d['mode']]);
        }
        if (isset($d['radius'])) {
            Settings::set('appearance.radius', max(0, min(32, (int) $d['radius'])));
            $changed[] = t('corner radius {n}px', ['n' => max(0, min(32, (int) $d['radius']))]);
        }
        return $changed;
    }

    /** The mode setting chosen by site/user/guest: dark | light | auto */
    /** A theme with supports.darkLight = false has ONE look: the mode is locked to its defaults.mode (the admin console ignores this). */
    public static function lockedMode(): ?string
    {
        $meta = View::chain()[0]['meta'] ?? [];
        if (($meta['supports']['darkLight'] ?? true) !== false) {
            return null;
        }
        return ($meta['defaults']['mode'] ?? 'dark') === 'light' ? 'light' : 'dark';
    }

    public static function modeSetting(bool $ignoreLock = false): string
    {
        if (!$ignoreLock && ($lock = self::lockedMode()) !== null) {
            return $lock;
        }
        $site = (string) setting('appearance.mode_default', 'dark');
        if (!setting('appearance.allow_user_mode', true)) {
            return $site;
        }
        $m = user()['theme_mode'] ?? ($_COOKIE['mcms_mode'] ?? null);
        return in_array($m, ['dark', 'light', 'auto'], true) ? $m : $site;
    }

    /** Initial resolved mode for server-side rendering (JS resolves "auto" before first paint). */
    public static function mode(bool $ignoreLock = false): string
    {
        $m = self::modeSetting($ignoreLock);
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
        $css .= '--radius:' . $r . 'px;--radius-sm:' . intdiv($r, 2) . 'px;--radius-lg:' . intdiv($r * 3, 2) . 'px;';
        if ($v = Layout::logoSize()) { $css .= '--logo-h:' . $v . 'px;'; }
        if ($v = Layout::nameSize()) { $css .= '--name-size:' . $v . 'px;'; }
        if ($v = Layout::nameColor()) { $css .= '--name-color:' . $v . ';'; }
        if ($v = Layout::sidebarW()) { $css .= '--sidebar-w:' . $v . 'px;'; }
        if ($v = Layout::containerW()) { $css .= '--container-w:' . $v . 'px;'; }
        return $css . '}';
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
