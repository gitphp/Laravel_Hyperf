<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Auth\AuthManager;

/**
 * @method static bool attempt(array $credentials)
 * @method static void login(\SunnyPHP\Auth\Authenticatable $user)
 * @method static void logout()
 * @method static bool check()
 * @method static bool guest()
 * @method static \SunnyPHP\Auth\Authenticatable|null user()
 * @method static int|string|null id()
 */
final class Auth extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return AuthManager::class;
    }
}
