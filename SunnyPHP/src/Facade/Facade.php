<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use RuntimeException;
use SunnyPHP\Application;

abstract class Facade
{
    protected static ?Application $app = null;

    public static function setFacadeApplication(Application $app): void
    {
        static::$app = $app;
    }

    abstract protected static function getFacadeAccessor(): string;

    public static function clearResolvedInstance(): void
    {
        static::$app = null;
    }

    public static function __callStatic(string $method, array $arguments): mixed
    {
        $instance = static::resolve();

        return $instance->{$method}(...$arguments);
    }

    protected static function resolve(): object
    {
        if (static::$app === null) {
            throw new RuntimeException('Facade application has not been set.');
        }

        $accessor = static::getFacadeAccessor();
        $resolved = static::$app->container->make($accessor);
        if (!is_object($resolved)) {
            throw new RuntimeException("Facade accessor [{$accessor}] did not resolve to an object.");
        }

        return $resolved;
    }
}
