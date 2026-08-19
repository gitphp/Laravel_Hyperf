<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Session\SessionManager;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void set(string $key, mixed $value)
 * @method static bool has(string $key)
 * @method static void forget(string $key)
 * @method static void flash(string $key, mixed $value)
 */
final class Session extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return SessionManager::class;
    }
}
