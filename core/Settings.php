<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** DB-backed key/value settings (values JSON-encoded). Loaded once per request. */
final class Settings
{
    private static array $data = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        self::$data = [];
        foreach (Bootstrap::$db->all('SELECT `key`, `value` FROM {settings}') as $r) {
            self::$data[$r['key']] = json_decode((string) $r['value'], true);
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            return $default;
        }
        return array_key_exists($key, self::$data) && self::$data[$key] !== null ? self::$data[$key] : $default;
    }

    public static function set(string $key, $value): void
    {
        Bootstrap::$db->exec(
            'INSERT INTO {settings} (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, json_encode($value, JSON_UNESCAPED_UNICODE)]
        );
        self::$data[$key] = $value;
    }
}
