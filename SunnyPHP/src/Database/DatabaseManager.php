<?php

declare(strict_types=1);

namespace SunnyPHP\Database;

use SunnyPHP\Config\Repository;

final class DatabaseManager
{
    /** @var array<string, Connection> */
    private array $connections = [];

    public function __construct(
        private Repository $config,
    ) {
    }

    public function connection(?string $name = null): Connection
    {
        $name ??= (string) $this->config->get('database.default', 'sqlite');

        return $this->connections[$name] ??= $this->makeConnection($name);
    }

    public function table(string $table): QueryBuilder
    {
        return $this->connection()->table($table);
    }

    /** @param array<int, mixed> $bindings */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->connection()->select($sql, $bindings);
    }

    /** @param array<int, mixed> $bindings */
    public function statement(string $sql, array $bindings = []): int
    {
        return $this->connection()->statement($sql, $bindings);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->connection()->transaction($callback);
    }

    private function makeConnection(string $name): Connection
    {
        /** @var array<string, mixed> $config */
        $config = $this->config->get("database.connections.{$name}");
        if (!is_array($config)) {
            throw new \InvalidArgumentException("Database connection [{$name}] is not configured.");
        }

        return new Connection($config);
    }
}
