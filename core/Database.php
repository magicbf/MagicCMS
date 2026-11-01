<?php
declare(strict_types=1);

namespace MagicCMS\Core;

use PDO;
use PDOStatement;

/**
 * Thin PDO wrapper. Prepared statements only.
 * Write table names as {users}; the configured prefix is added automatically.
 */
final class Database
{
    private PDO $pdo;
    private string $prefix;

    public function __construct(array $c)
    {
        $this->prefix = (string) ($c['prefix'] ?? 'mcms_');
        if (!preg_match('/^[a-z][a-z0-9_]{0,15}$/', $this->prefix)) {
            throw new \InvalidArgumentException('Invalid table prefix');
        }
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? 'localhost',
            (int) ($c['port'] ?? 3306),
            $c['name'] ?? ''
        );
        $this->pdo = new PDO($dsn, (string) ($c['user'] ?? ''), (string) ($c['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+00:00'",
        ]);
    }

    /** Raw connection (backup/restore streaming only). */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function sql(string $sql): string
    {
        return preg_replace('/\{([a-z_]+)\}/', $this->prefix . '$1', $sql);
    }

    public function q(string $sql, array $params = []): PDOStatement
    {
        $st = $this->pdo->prepare($this->sql($sql));
        $st->execute($params);
        return $st;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->q($sql, $params)->fetchAll();
    }

    public function one(string $sql, array $params = []): ?array
    {
        $r = $this->q($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public function val(string $sql, array $params = [])
    {
        $r = $this->q($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    public function exec(string $sql, array $params = []): int
    {
        return $this->q($sql, $params)->rowCount();
    }

    public function insert(string $sql, array $params = []): int
    {
        $this->q($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /** Run raw DDL (setup only). */
    public function raw(string $sql): void
    {
        $this->pdo->exec($this->sql($sql));
    }

    public function transaction(callable $fn)
    {
        $this->pdo->beginTransaction();
        try {
            $r = $fn($this);
            $this->pdo->commit();
            return $r;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
