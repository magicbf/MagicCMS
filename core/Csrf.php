<?php
declare(strict_types=1);

namespace MagicCMS\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verify(): bool
    {
        $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return is_string($sent) && $sent !== '' && hash_equals(self::token(), $sent);
    }
}
