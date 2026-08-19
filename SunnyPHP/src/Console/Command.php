<?php

declare(strict_types=1);

namespace SunnyPHP\Console;

abstract class Command
{
    abstract public function name(): string;

    abstract public function description(): string;

    /** @param list<string> $args */
    abstract public function handle(array $args): int;
}
