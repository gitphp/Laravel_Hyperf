<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Application;
use SunnyPHP\Console\Command;

final class ServeCommand extends Command
{
    public function __construct(
        private Application $app,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'serve';
    }

    #[\Override]
    public function description(): string
    {
        return 'Start the PHP built-in development server';
    }

    #[\Override]
    public function handle(array $args): int
    {
        $host = $args[0] ?? '127.0.0.1';
        $port = $args[1] ?? '8000';
        $public = $this->app->path('public');
        $router = $public . DIRECTORY_SEPARATOR . 'index.php';

        $command = sprintf(
            '%s -S %s -t %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg("{$host}:{$port}"),
            escapeshellarg($public),
            escapeshellarg($router),
        );

        echo "SunnyPHP development server started: http://{$host}:{$port}\n";
        passthru($command, $code);

        return (int) $code;
    }
}
