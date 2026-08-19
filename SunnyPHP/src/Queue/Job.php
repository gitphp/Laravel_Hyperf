<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

abstract class Job
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public array $payload = [],
    ) {
    }

    abstract public function handle(): void;
}
