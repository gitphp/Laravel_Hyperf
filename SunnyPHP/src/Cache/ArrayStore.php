<?php

declare(strict_types=1);

namespace SunnyPHP\Cache;

use DateInterval;
use Traversable;

final class ArrayStore implements Store
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private array $items = [];

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]['value'];
    }

    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->items[$key] = [
            'value' => $value,
            'expires' => $this->expiresAt($ttl),
        ];

        return true;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    #[\Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($this->keys($keys) as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    #[\Override]
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    #[\Override]
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($this->keys($keys) as $key) {
            $this->delete($key);
        }

        return true;
    }

    #[\Override]
    public function has(string $key): bool
    {
        if (!isset($this->items[$key])) {
            return false;
        }

        $expires = $this->items[$key]['expires'];
        if ($expires !== 0 && $expires < time()) {
            unset($this->items[$key]);

            return false;
        }

        return true;
    }

    #[\Override]
    public function remember(string $key, int|DateInterval|null $ttl, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function expiresAt(DateInterval|int|null $ttl): int
    {
        if ($ttl === null) {
            return 0;
        }

        if ($ttl instanceof DateInterval) {
            return (new \DateTimeImmutable())->add($ttl)->getTimestamp();
        }

        return $ttl <= 0 ? time() - 1 : time() + $ttl;
    }

    /** @return list<string> */
    private function keys(iterable $keys): array
    {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys, false);
        }

        return array_map(strval(...), array_values($keys));
    }
}
