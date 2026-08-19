<?php

declare(strict_types=1);

namespace SunnyPHP\Database;

use InvalidArgumentException;

final class QueryBuilder
{
    /** @var list<string> */
    private array $columns = ['*'];

    /** @var list<array{type: string, sql: string, boolean: string}> */
    private array $wheres = [];

    /** @var list<mixed> */
    private array $bindings = [];

    /** @var list<array{column: string, direction: string}> */
    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    public function __construct(
        private Connection $connection,
        private string $table,
    ) {
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns === [] ? ['*'] : array_values($columns);

        return $this;
    }

    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere($column, (string) $operator, $value, 'AND');
    }

    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->addWhere($column, (string) $operator, $value, 'OR');
    }

    /** @param list<mixed> $values */
    public function whereIn(string $column, array $values): self
    {
        if ($values === []) {
            $this->wheres[] = ['type' => 'raw', 'sql' => '0 = 1', 'boolean' => 'AND'];

            return $this;
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [
            'type' => 'in',
            'sql' => $this->wrap($column) . " IN ({$placeholders})",
            'boolean' => 'AND',
        ];
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = ['column' => $column, 'direction' => $direction];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /** @return list<array<string, mixed>> */
    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->bindings);
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        $rows = (clone $this)->limit(1)->get();

        return $rows[0] ?? null;
    }

    public function value(string $column): mixed
    {
        $row = (clone $this)->select($column)->first();

        return $row[$column] ?? null;
    }

    public function count(): int
    {
        $row = $this->connection->selectOne(
            'SELECT COUNT(*) AS "aggregate" FROM ' . $this->wrap($this->table) . $this->compileWheres(),
            $this->bindings,
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    /** @param array<string, mixed> $values */
    public function insert(array $values): bool
    {
        if ($values === []) {
            return false;
        }

        $columns = array_map($this->wrap(...), array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $sql = 'INSERT INTO ' . $this->wrap($this->table)
            . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';

        $this->connection->statement($sql, array_values($values));

        return true;
    }

    /** @param array<string, mixed> $values */
    public function insertGetId(array $values): string
    {
        $this->insert($values);

        return $this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $values */
    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        $sets = [];
        $bindings = [];
        foreach ($values as $column => $value) {
            $sets[] = $this->wrap($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = 'UPDATE ' . $this->wrap($this->table)
            . ' SET ' . implode(', ', $sets)
            . $this->compileWheres();

        return $this->connection->statement($sql, [...$bindings, ...$this->bindings]);
    }

    public function delete(): int
    {
        $sql = 'DELETE FROM ' . $this->wrap($this->table) . $this->compileWheres();

        return $this->connection->statement($sql, $this->bindings);
    }

    public function toSql(): string
    {
        $columns = $this->columns === ['*']
            ? '*'
            : implode(', ', array_map($this->wrap(...), $this->columns));

        $sql = 'SELECT ' . $columns . ' FROM ' . $this->wrap($this->table) . $this->compileWheres();

        if ($this->orders !== []) {
            $orders = array_map(
                fn (array $order): string => $this->wrap($order['column']) . ' ' . $order['direction'],
                $this->orders,
            );
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    private function addWhere(string $column, string $operator, mixed $value, string $boolean): self
    {
        $operator = strtoupper($operator);
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];
        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException("Illegal operator [{$operator}].");
        }

        $this->wheres[] = [
            'type' => 'basic',
            'sql' => $this->wrap($column) . " {$operator} ?",
            'boolean' => $boolean,
        ];
        $this->bindings[] = $value;

        return $this;
    }

    private function compileWheres(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $parts = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? '' : ' ' . $where['boolean'] . ' ';
            $parts[] = $prefix . $where['sql'];
        }

        return ' WHERE ' . implode('', $parts);
    }

    private function wrap(string $value): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new InvalidArgumentException("Invalid identifier [{$value}].");
        }

        $quote = $this->connection->driver() === 'mysql' ? '`' : '"';

        return $quote . $value . $quote;
    }
}
