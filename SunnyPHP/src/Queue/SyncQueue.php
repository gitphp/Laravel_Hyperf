<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

use SunnyPHP\Container\Container;

final class SyncQueue implements QueueDriver
{
    public function __construct(
        private Container $container,
    ) {
    }

    #[\Override]
    public function push(string $job, array $payload = [], int $delay = 0, string $queue = 'default'): string
    {
        $this->run($job, $payload);

        return 'sync';
    }

    #[\Override]
    public function pop(string $queue = 'default'): ?QueuedJob
    {
        return null;
    }

    #[\Override]
    public function later(int $delay, string $job, array $payload = [], string $queue = 'default'): string
    {
        return $this->push($job, $payload, $delay, $queue);
    }

    /** @param array<string, mixed> $payload */
    private function run(string $job, array $payload): void
    {
        $instance = $this->container->make($job, ['payload' => $payload]);
        if ($instance instanceof Job) {
            $instance->payload = $payload;
            $instance->handle();

            return;
        }

        if (is_callable($instance)) {
            $instance($payload);
        }
    }
}
