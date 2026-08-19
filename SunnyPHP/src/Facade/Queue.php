<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Queue\QueueManager;

/**
 * @method static string push(string|\SunnyPHP\Queue\Job $job, array $payload = [], string $queue = 'default')
 * @method static string later(int $delay, string|\SunnyPHP\Queue\Job $job, array $payload = [], string $queue = 'default')
 * @method static \SunnyPHP\Queue\QueuedJob|null pop(string $queue = 'default')
 */
final class Queue extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return QueueManager::class;
    }
}
