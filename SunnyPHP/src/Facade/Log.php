<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use Psr\Log\LoggerInterface;

/**
 * @method static void info(string|\Stringable $message, array $context = [])
 * @method static void error(string|\Stringable $message, array $context = [])
 * @method static void warning(string|\Stringable $message, array $context = [])
 * @method static void debug(string|\Stringable $message, array $context = [])
 */
final class Log extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return LoggerInterface::class;
    }
}
