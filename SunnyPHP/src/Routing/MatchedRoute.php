<?php

declare(strict_types=1);

namespace SunnyPHP\Routing;

final readonly class MatchedRoute
{
    /** @param array<string, string> $parameters */
    public function __construct(
        public Route $route,
        public array $parameters,
    ) {
    }
}
