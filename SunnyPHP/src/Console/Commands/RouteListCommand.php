<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Console\Command;
use SunnyPHP\Routing\Router;

final class RouteListCommand extends Command
{
    public function __construct(
        private Router $router,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'route:list';
    }

    #[\Override]
    public function description(): string
    {
        return 'List registered HTTP routes';
    }

    #[\Override]
    public function handle(array $args): int
    {
        $routes = $this->router->routes();
        if ($routes === []) {
            echo "No routes registered.\n";

            return 0;
        }

        echo sprintf("%-12s %-32s %s\n", 'METHOD', 'URI', 'HANDLER');
        echo str_repeat('-', 80) . "\n";

        foreach ($routes as $route) {
            echo sprintf(
                "%-12s %-32s %s\n",
                implode('|', $route->methods),
                $route->path,
                $route->handlerLabel(),
            );
        }

        return 0;
    }
}
