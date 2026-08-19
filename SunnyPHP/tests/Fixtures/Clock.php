<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

final class Clock
{
    public function now(): string
    {
        return 'now';
    }
}
