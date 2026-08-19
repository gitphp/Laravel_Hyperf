<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

final class Greeter
{
    public function __construct(
        public Clock $clock,
    ) {
    }

    public function greet(): string
    {
        return 'hello@' . $this->clock->now();
    }
}
