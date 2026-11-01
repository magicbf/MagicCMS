<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Minimal event/filter system: plugins subscribe without touching core. */
final class Hooks
{
    private static array $listeners = [];

    public static function on(string $name, callable $cb, int $priority = 10): void
    {
        self::$listeners[$name][$priority][] = $cb;
    }

    private static function each(string $name): iterable
    {
        $by = self::$listeners[$name] ?? [];
        ksort($by);
        foreach ($by as $cbs) {
            yield from $cbs;
        }
    }

    /** Fire an action; returns each listener's return value. */
    public static function fire(string $name, ...$args): array
    {
        $out = [];
        foreach (self::each($name) as $cb) {
            $out[] = $cb(...$args);
        }
        return $out;
    }

    /** Pass $value through every listener (each returns the new value). */
    public static function filter(string $name, $value, ...$args)
    {
        foreach (self::each($name) as $cb) {
            $value = $cb($value, ...$args);
        }
        return $value;
    }

    /** Concatenate string results (used for template injection points). */
    public static function html(string $name, ...$args): string
    {
        return implode('', array_filter(self::fire($name, ...$args), 'is_string'));
    }
}
