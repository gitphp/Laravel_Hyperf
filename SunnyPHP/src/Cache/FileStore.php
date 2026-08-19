<?php

declare(strict_types=1);

namespace SunnyPHP\Cache;

use DateInterval;
use Traversable;

final class FileStore implements Store
{
    public function __construct(
        private string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    #[\Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return $default;
        }

        $payload = unserialize((string) file_get_contents($path), ['allowed_classes' => true]);
        if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('value', $payload)) {
            @unlink($path);

            return $default;
        }

        if ($payload['expires'] !== 0 && $payload['expires'] < time()) {
            @unlink($path);

            return $default;
        }

        return $payload['value'];
    }

    #[\Override]
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $payload = serialize([
            'value' => $value,
            'expires' => $this->expiresAt($ttl),
        ]);

        return file_put_contents($this->path($key), $payload, LOCK_EX) !== false;
    }

    #[\Override]
    public function delete(string $key): bool
    {
        $path = $this->path($key);
        if (is_file($path)) {
            return @unlink($path);
        }

        return true;
    }

    #[\Override]
    public function clear(): bool
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $file) {
            @unlink($file);
        }

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
        return $this->get($key, $this) !== $this;
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

    private function path(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.cache';
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
