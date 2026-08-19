<?php

declare(strict_types=1);

namespace SunnyPHP\Aop;

final class ProceedingJoinPoint
{
    /**
     * @param array<int|string, mixed> $arguments
     */
    public function __construct(
        public private(set) object $target,
        public private(set) string $method,
        public array $arguments,
        private \Closure $pipeline,
    ) {
    }

    /** @param array<int|string, mixed>|null $arguments */
    public function proceed(?array $arguments = null): mixed
    {
        return ($this->pipeline)($arguments ?? $this->arguments);
    }
}
