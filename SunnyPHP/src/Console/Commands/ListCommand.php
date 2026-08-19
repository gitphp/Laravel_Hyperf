<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Console\Command;
use SunnyPHP\Console\Kernel;
use SunnyPHP\Container\Container;

final class ListCommand extends Command
{
    public function __construct(
        private Kernel $kernel,
        private Container $container,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'list';
    }

    #[\Override]
    public function description(): string
    {
        return 'List available commands';
    }

    #[\Override]
    public function handle(array $args): int
    {
        echo "SunnyPHP " . \SunnyPHP\Application::VERSION . "\n\n";
        echo "Available commands:\n";

        foreach ($this->kernel->commandMap() as $name => $class) {
            /** @var Command $command */
            $command = $this->container->make($class);
            echo sprintf("  %-12s %s\n", $name, $command->description());
        }

        return 0;
    }
}
