<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Cache\CacheManager;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string $key, mixed $value, \DateInterval|int|null $ttl = null)
 * @method static bool delete(string $key)
 * @method static bool has(string $key)
 * @method static mixed remember(string $key, \DateInterval|int|null $ttl, callable $callback)
 * @method static \SunnyPHP\Cache\Store store(?string $name = null)
 */
final class Cache extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return CacheManager::class;
    }
}
