<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

use InvalidArgumentException;
use SunnyPHP\Application;
use SunnyPHP\Config\Repository;
use SunnyPHP\Container\Container;
use SunnyPHP\Database\DatabaseManager;

final class QueueManager
{
    /** @var array<string, QueueDriver> */
    private array $connections = [];

    public function __construct(
        private Repository $config,
        private Container $container,
        private Application $app,
    ) {
    }

    public function connection(?string $name = null): QueueDriver
    {
        $name ??= (string) $this->config->get('queue.default', 'sync');

        return $this->connections[$name] ??= $this->resolve($name);
    }

    /**
     * @param class-string<Job>|Job $job
     * @param array<string, mixed> $payload
     */
    public function push(string|Job $job, array $payload = [], string $queue = 'default'): string
    {
        if ($job instanceof Job) {
            $payload = $job->payload + $payload;
            $job = $job::class;
        }

        return $this->connection()->push($job, $payload, 0, $queue);
    }

    /**
     * @param class-string<Job>|Job $job
     * @param array<string, mixed> $payload
     */
    public function later(int $delay, string|Job $job, array $payload = [], string $queue = 'default'): string
    {
        if ($job instanceof Job) {
            $payload = $job->payload + $payload;
            $job = $job::class;
        }

        return $this->connection()->later($delay, $job, $payload, $queue);
    }

    public function pop(string $queue = 'default'): ?QueuedJob
    {
        return $this->connection()->pop($queue);
    }

    public function run(QueuedJob $job): void
    {
        $instance = $this->container->make($job->class, ['payload' => $job->payload]);
        if ($instance instanceof Job) {
            $instance->payload = $job->payload;
            $instance->handle();
        }
    }

    private function resolve(string $name): QueueDriver
    {
        $driver = (string) $this->config->get("queue.connections.{$name}.driver", $name);

        return match ($driver) {
            'sync' => new SyncQueue($this->container),
            'file' => new FileQueue(
                (string) $this->config->get("queue.connections.{$name}.path", $this->app->path('storage/framework/queue')),
            ),
            'database' => new DatabaseQueue($this->container->make(DatabaseManager::class)),
            default => throw new InvalidArgumentException("Unsupported queue driver [{$driver}]."),
        };
    }
}
