<?php

declare(strict_types=1);

namespace SunnyPHP\Console;

use SunnyPHP\Application;
use SunnyPHP\Console\Commands\CacheClearCommand;
use SunnyPHP\Console\Commands\ListCommand;
use SunnyPHP\Console\Commands\MigrateCommand;
use SunnyPHP\Console\Commands\QueueWorkCommand;
use SunnyPHP\Console\Commands\RouteListCommand;
use SunnyPHP\Console\Commands\ServeCommand;

final class Kernel
{
    /** @var array<string, class-string<Command>> */
    private const array COMMANDS = [
        'list' => ListCommand::class,
        'serve' => ServeCommand::class,
        'route:list' => RouteListCommand::class,
        'migrate' => MigrateCommand::class,
        'cache:clear' => CacheClearCommand::class,
        'queue:work' => QueueWorkCommand::class,
    ];

    public function __construct(
        private Application $app,
    ) {
    }

    /** @param list<string> $argv */
    public function run(array $argv): int
    {
        $this->app->container->instance(self::class, $this);

        $name = $argv[1] ?? 'list';
        $class = self::COMMANDS[$name] ?? null;
        if ($class === null) {
            fwrite(STDERR, "Unknown command: {$name}\n");

            return 1;
        }

        /** @var Command $command */
        $command = $this->app->container->make($class);

        return $command->handle(array_values(array_slice($argv, 2)));
    }

    /** @return array<string, class-string<Command>> */
    public function commandMap(): array
    {
        return self::COMMANDS;
    }
}
