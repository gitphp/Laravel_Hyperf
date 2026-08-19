<?php

declare(strict_types=1);

namespace SunnyPHP\Cache;

use DateInterval;
use InvalidArgumentException;
use SunnyPHP\Application;
use SunnyPHP\Config\Repository;

final class CacheManager
{
    /** @var array<string, Store> */
    private array $stores = [];

    public function __construct(
        private Repository $config,
        private Application $app,
    ) {
    }

    public function store(?string $name = null): Store
    {
        $name ??= (string) $this->config->get('cache.default', 'file');

        return $this->stores[$name] ??= $this->resolve($name);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function remember(string $key, int|DateInterval|null $ttl, callable $callback): mixed
    {
        return $this->store()->remember($key, $ttl, $callback);
    }

    public function clear(): bool
    {
        return $this->store()->clear();
    }

    private function resolve(string $name): Store
    {
        $driver = (string) $this->config->get("cache.stores.{$name}.driver", $name);

        return match ($driver) {
            'array' => new ArrayStore(),
            'file' => new FileStore(
                (string) $this->config->get("cache.stores.{$name}.path", $this->app->path('storage/framework/cache')),
            ),
            default => throw new InvalidArgumentException("Unsupported cache driver [{$driver}]."),
        };
    }
}
