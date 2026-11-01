<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Template + theme resolution.
 *
 * render('layout')        → themes/<t>/templates/layout.php   | views/layout.php
 * render('auth/login')    → themes/<t>/templates/auth/login.php | modules/auth/views/login.php
 * Themes may declare "parent" in theme.json; the chain is searched child → parent.
 */
final class View
{
    public static ?string $routeLayout = null;
    private static ?array $chain = null;

    /** @return array<int,array{slug:string,dir:string,meta:array}> child first */
    public static function chain(): array
    {
        if (self::$chain !== null) {
            return self::$chain;
        }
        self::$chain = [];
        $slug = (string) setting('theme.active', 'magic-red');
        $seen = [];
        while ($slug && !isset($seen[$slug]) && count($seen) < 5) {
            $seen[$slug] = true;
            $dir = MCMS_ROOT . '/themes/' . $slug;
            $meta = is_file($dir . '/theme.json') ? (json_decode((string) file_get_contents($dir . '/theme.json'), true) ?: []) : null;
            if ($meta === null || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
                break;
            }
            self::$chain[] = ['slug' => $slug, 'dir' => $dir, 'meta' => $meta];
            $slug = (string) ($meta['parent'] ?? '');
        }
        if (!self::$chain) { // active theme broken → fall back to the default theme
            $dir = MCMS_ROOT . '/themes/magic-red';
            self::$chain[] = ['slug' => 'magic-red', 'dir' => $dir, 'meta' => json_decode((string) @file_get_contents($dir . '/theme.json'), true) ?: []];
        }
        return self::$chain;
    }

    /** All installed themes: slug => meta. */
    public static function themes(): array
    {
        $out = [];
        foreach (glob(MCMS_ROOT . '/themes/*/theme.json') ?: [] as $f) {
            $meta = json_decode((string) file_get_contents($f), true);
            $slug = basename(dirname($f));
            if (is_array($meta) && preg_match('/^[a-z0-9\-]+$/', $slug)) {
                $out[$slug] = $meta + ['name' => $slug];
            }
        }
        return $out;
    }

    public static function find(string $name): ?string
    {
        if (!preg_match('#^[a-z0-9_\-]+(/[a-z0-9_\-]+)?$#i', $name)) {
            return null;
        }
        foreach (self::chain() as $t) {
            $f = $t['dir'] . '/templates/' . $name . '.php';
            if (is_file($f)) {
                return $f;
            }
        }
        if (str_contains($name, '/')) {
            [$mod, $view] = explode('/', $name, 2);
            $f = MCMS_ROOT . '/modules/' . $mod . '/views/' . $view . '.php';
        } else {
            $f = MCMS_ROOT . '/views/' . $name . '.php';
        }
        return is_file($f) ? $f : null;
    }

    public static function render(string $name, array $data = []): string
    {
        $file = self::find($name);
        if (!$file) {
            throw new \RuntimeException("Template not found: $name");
        }
        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })($file, $data);
    }

    /** Wrap content in the site layout (header, panels, footer). */
    public static function page(string $title, string $content, array $o = []): string
    {
        $layout = Layout::resolve($o['layout'] ?? self::$routeLayout);
        return self::render('layout', [
            'title' => $title,
            'content' => $content,
            'layout' => $layout,
            'bodyClass' => trim(($o['class'] ?? '')),
            'description' => $o['description'] ?? (string) setting('site.description', ''),
            'mode' => Appearance::mode(),
            'modeSetting' => Appearance::modeSetting(),
        ]);
    }

    /** <link>/<script> tags for core + the theme chain (parents first). */
    public static function assets(string $where): string
    {
        $out = '';
        $chain = array_reverse(self::chain());
        if ($where === 'head') {
            $out .= '<link rel="stylesheet" href="' . e(asset('core.css')) . '">' . "\n";
            foreach ($chain as $t) {
                foreach ($t['meta']['stylesheets'] ?? ['tokens.css', 'theme.css'] as $css) {
                    if (is_file($t['dir'] . '/' . $css)) {
                        $out .= '<link rel="stylesheet" href="' . e(url('themes/' . $t['slug'] . '/' . $css) . '?v=' . ($t['meta']['version'] ?? '0')) . '">' . "\n";
                    }
                }
            }
            $out .= '<style id="mcms-scheme" nonce="' . e(Bootstrap::$nonce) . '">' . Appearance::css() . '</style>' . "\n";
        } else {
            $out .= '<script src="' . e(asset('core.js')) . '" defer></script>' . "\n";
            foreach ($chain as $t) {
                foreach ($t['meta']['scripts'] ?? [] as $js) {
                    if (is_file($t['dir'] . '/' . $js)) {
                        $out .= '<script src="' . e(url('themes/' . $t['slug'] . '/' . $js) . '?v=' . ($t['meta']['version'] ?? '0')) . '" defer></script>' . "\n";
                    }
                }
            }
        }
        return $out;
    }
}
