<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Database\DatabaseManager;

/**
 * @method static \SunnyPHP\Database\Connection connection(?string $name = null)
 * @method static \SunnyPHP\Database\QueryBuilder table(string $table)
 * @method static array select(string $sql, array $bindings = [])
 * @method static int statement(string $sql, array $bindings = [])
 * @method static mixed transaction(callable $callback)
 */
final class DB extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return DatabaseManager::class;
    }
}
