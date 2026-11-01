<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/**
 * Panel registry + renderer.
 * A panel is a folder with panel.json (metadata/settings schema) and panel.php (inner HTML).
 * Folders are discovered in panels/ and plugins/<name>/panels/.
 */
final class Panels
{
    private static ?array $registry = null;

    /** @return array<string,array{dir:string,meta:array}> */
    public static function registry(): array
    {
        if (self::$registry === null) {
            self::$registry = [];
            $files = array_merge(
                glob(MCMS_ROOT . '/panels/*/panel.json') ?: [],
                glob(MCMS_ROOT . '/plugins/*/panels/*/panel.json') ?: []
            );
            foreach ($files as $f) {
                $meta = json_decode((string) file_get_contents($f), true);
                $key = basename(dirname($f));
                if (is_array($meta) && preg_match('/^[a-z0-9_]+$/', $key) && is_file(dirname($f) . '/panel.php')) {
                    self::$registry[$key] = ['dir' => dirname($f), 'meta' => $meta + ['title' => $key]];
                }
            }
        }
        return self::$registry;
    }

    /** Default values from the panel's settings schema merged with stored values. */
    public static function settings(string $key, ?string $json): array
    {
        $defaults = [];
        foreach ((self::registry()[$key]['meta']['settings'] ?? []) as $name => $def) {
            $defaults[$name] = $def['default'] ?? '';
        }
        $stored = $json ? (json_decode($json, true) ?: []) : [];
        return array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    private static function visible(array $p): bool
    {
        $v = json_decode((string) $p['visibility'], true) ?: [];
        $who = $v['who'] ?? 'all';
        $u = user();
        return match ($who) {
            'guests' => $u === null,
            'members' => $u !== null,
            'staff' => Auth::level() >= 50,
            default => true,
        };
    }

    /** @return array<int,string> rendered panel wrappers for a column */
    public static function render(string $position): array
    {
        $out = [];
        $rows = db()->all('SELECT * FROM {panels} WHERE enabled = 1 AND position = ? ORDER BY sort_order, id', [$position]);
        foreach ($rows as $p) {
            $reg = self::registry()[$p['panel_key']] ?? null;
            if (!$reg || !self::visible($p)) {
                continue;
            }
            try {
                $html = trim((new class {
                    public function run(string $file, array $vars): string
                    {
                        extract($vars, EXTR_SKIP);
                        ob_start();
                        try {
                            include $file;
                            return (string) ob_get_clean();
                        } catch (\Throwable $e) {
                            ob_end_clean();
                            throw $e;
                        }
                    }
                })->run($reg['dir'] . '/panel.php', [
                    'settings' => self::settings($p['panel_key'], $p['settings']),
                    'user' => user(),
                    'panel' => $p,
                ]));
            } catch (\Throwable $e) {
                error_log('MagicCMS panel ' . $p['panel_key'] . ': ' . $e->getMessage());
                continue; // one broken panel must never take the page down
            }
            if ($html === '') {
                continue;
            }
            $v = json_decode((string) $p['visibility'], true) ?: [];
            $out[] = render('panel', [
                'panel' => ['id' => (int) $p['id'], 'key' => $p['panel_key'], 'title' => $p['title'], 'hideMobile' => empty($v['mobile'] ?? true)],
                'html' => $html,
            ]);
        }
        return $out;
    }
}
