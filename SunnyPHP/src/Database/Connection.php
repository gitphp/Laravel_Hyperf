<?php

declare(strict_types=1);

namespace SunnyPHP\Database;

use InvalidArgumentException;
use PDO;
use PDOException;

final class Connection
{
    private PDO $pdo;

    /** @param array<string, mixed> $config */
    public function __construct(
        private array $config,
    ) {
        $this->pdo = $this->connect($config);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'sqlite');
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }

    /** @param array<int, mixed> $bindings */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->fetchAll();
    }

    /** @param array<int, mixed> $bindings */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $rows = $this->select($sql, $bindings);

        return $rows[0] ?? null;
    }

    /** @param array<int, mixed> $bindings */
    public function statement(string $sql, array $bindings = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);

        return $statement->rowCount();
    }

    public function lastInsertId(): string
    {
        return (string) $this->pdo->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<string, mixed> $config */
    private function connect(array $config): PDO
    {
        $driver = (string) ($config['driver'] ?? 'sqlite');

        try {
            return match ($driver) {
                'sqlite' => $this->connectSqlite($config),
                'mysql' => $this->connectMysql($config),
                default => throw new InvalidArgumentException("Unsupported database driver [{$driver}]."),
            };
        } catch (PDOException $e) {
            throw new PDOException('Could not connect to the database: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /** @param array<string, mixed> $config */
    private function connectSqlite(array $config): PDO
    {
        $database = (string) ($config['database'] ?? ':memory:');
        if ($database !== ':memory:') {
            $directory = dirname($database);
            if ($directory !== '' && $directory !== '.' && !is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        return new PDO('sqlite:' . $database);
    }

    /** @param array<string, mixed> $config */
    private function connectMysql(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4',
        );

        return new PDO(
            $dsn,
            (string) ($config['username'] ?? 'root'),
            (string) ($config['password'] ?? ''),
        );
    }
}
