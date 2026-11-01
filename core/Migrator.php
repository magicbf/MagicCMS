<?php
declare(strict_types=1);

namespace MagicCMS\Core;

require_once __DIR__ . '/Database.php';

/**
 * Versioned migrations in migrations/NNNN_name.sql (schema) with an optional NNNN_name.php
 * (data seeding, returns function (Database $db): void). The applied version lives in the
 * `schema.version` setting. Fresh installs and upgrades use the same path.
 */
final class Migrator
{
    /** @return array<int,array{sql:string,php:?string}> */
    public static function files(): array
    {
        $out = [];
        foreach (glob(dirname(__DIR__) . '/migrations/[0-9][0-9][0-9][0-9]_*.sql') ?: [] as $f) {
            $v = (int) substr(basename($f), 0, 4);
            $php = substr($f, 0, -4) . '.php';
            $out[$v] = ['sql' => $f, 'php' => is_file($php) ? $php : null];
        }
        ksort($out);
        return $out;
    }

    public static function latest(): int
    {
        $f = self::files();
        return $f ? max(array_keys($f)) : 1;
    }

    public static function current(Database $db): int
    {
        $v = $db->val("SELECT `value` FROM {settings} WHERE `key` = 'schema.version'");
        return $v === null ? 1 : (int) json_decode((string) $v);
    }

    /** Apply every migration newer than the current version. @return int new version */
    public static function upgrade(Database $db): int
    {
        $locked = (int) $db->val("SELECT GET_LOCK('mcms_migrate', 15)");
        try {
            $cur = self::current($db);
            foreach (self::files() as $v => $f) {
                if ($v <= $cur || $v === 1) {
                    continue;
                }
                $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($f['sql']));
                foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
                    $db->raw($stmt);
                }
                if ($f['php']) {
                    (require $f['php'])($db);
                }
                $db->exec("INSERT INTO {settings} (`key`, `value`) VALUES ('schema.version', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)", [json_encode($v)]);
                $cur = $v;
            }
            return $cur;
        } finally {
            if ($locked) {
                $db->val("SELECT RELEASE_LOCK('mcms_migrate')");
            }
        }
    }
}
