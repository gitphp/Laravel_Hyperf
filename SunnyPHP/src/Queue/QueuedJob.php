<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

final readonly class QueuedJob
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $class,
        public array $payload,
        public int $attempts,
        public string $queue = 'default',
    ) {
    }
}
