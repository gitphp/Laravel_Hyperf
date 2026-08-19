<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

interface QueueDriver
{
    public function push(string $job, array $payload = [], int $delay = 0, string $queue = 'default'): string;

    public function pop(string $queue = 'default'): ?QueuedJob;

    public function later(int $delay, string $job, array $payload = [], string $queue = 'default'): string;
}
