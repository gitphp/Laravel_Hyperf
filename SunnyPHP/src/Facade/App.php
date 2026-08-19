<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Application;

/**
 * @method static \SunnyPHP\Container\Container container
 * @method static mixed make(string $id, array $parameters = [])
 * @method static string path(string $path = '')
 * @method static \SunnyPHP\Application boot()
 */
final class App extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return Application::class;
    }
}
