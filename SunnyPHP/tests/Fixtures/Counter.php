<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

class Counter
{
    public function add(int $n): int
    {
        return $n + 1;
    }
}
