<?php

declare(strict_types=1);

namespace SunnyPHP\Database;

abstract class Model
{
    protected static DatabaseManager $db;

    protected string $table;

    protected string $primaryKey = 'id';

    /** @var list<string> */
    protected array $fillable = [];

    /** @var array<string, mixed> */
    protected array $attributes = [];

    protected bool $exists = false;

    public static function useDatabase(DatabaseManager $db): void
    {
        static::$db = $db;
    }

    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function query(): QueryBuilder
    {
        return static::$db->connection()->table((new static())->table);
    }

    public static function find(int|string $id): ?static
    {
        $model = new static();
        $row = static::query()->where($model->primaryKey, $id)->first();
        if ($row === null) {
            return null;
        }

        return $model->newFromRow($row);
    }

    /** @return list<static> */
    public static function all(): array
    {
        $model = new static();

        return array_map(
            fn (array $row): static => (new static())->newFromRow($row),
            static::query()->get(),
        );
    }

    /** @param array<string, mixed> $attributes */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /** @param array<string, mixed> $attributes */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->fillable === [] || in_array($key, $this->fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    public function save(): bool
    {
        $key = $this->primaryKey;

        if ($this->exists) {
            $id = $this->attributes[$key] ?? null;
            $values = $this->attributes;
            unset($values[$key]);
            static::query()->where($key, $id)->update($values);

            return true;
        }

        $id = static::query()->insertGetId($this->attributes);
        $this->attributes[$key] = is_numeric($id) ? (int) $id : $id;
        $this->exists = true;

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $key = $this->primaryKey;
        static::query()->where($key, $this->attributes[$key])->delete();
        $this->exists = false;

        return true;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /** @param array<string, mixed> $row */
    public static function hydrate(array $row): static
    {
        return (new static())->newFromRow($row);
    }

    /** @param array<string, mixed> $row */
    private function newFromRow(array $row): static
    {
        $this->attributes = $row;
        $this->exists = true;

        return $this;
    }
}
