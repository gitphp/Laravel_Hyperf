<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Config\Repository;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 */
final class Config extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return Repository::class;
    }
}
