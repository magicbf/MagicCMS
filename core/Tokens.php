<?php
declare(strict_types=1);

namespace MagicCMS\Core;

/** Single-use, expiring, hashed tokens for e-mail verification and password reset. */
final class Tokens
{
    public static function create(int $userId, string $type, int $ttlSeconds, ?string $newEmail = null): string
    {
        db()->exec('DELETE FROM {email_tokens} WHERE user_id = ? AND type = ?', [$userId, $type]); // one live token per purpose
        $token = bin2hex(random_bytes(24));
        db()->exec(
            'INSERT INTO {email_tokens} (user_id, type, token_hash, new_email, expires_at, created_at) VALUES (?,?,?,?,?,?)',
            [$userId, $type, hash('sha256', $token), $newEmail, gmdate('Y-m-d H:i:s', time() + $ttlSeconds), now()]
        );
        if (random_int(1, 25) === 1) {
            db()->exec('DELETE FROM {email_tokens} WHERE expires_at < ?', [gmdate('Y-m-d H:i:s', time() - 86400)]);
        }
        return $token;
    }

    /** Look up a valid token without consuming it. */
    public static function peek(string $token, string $type): ?array
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
            return null;
        }
        $row = db()->one('SELECT * FROM {email_tokens} WHERE token_hash = ? AND type = ?', [hash('sha256', $token), $type]);
        return $row && $row['expires_at'] > now() ? $row : null;
    }

    public static function burn(int $id): void
    {
        db()->exec('DELETE FROM {email_tokens} WHERE id = ?', [$id]);
    }
}
