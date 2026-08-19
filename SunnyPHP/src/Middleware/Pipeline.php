<?php

declare(strict_types=1);

namespace SunnyPHP\Middleware;

use SunnyPHP\Container\Container;
use SunnyPHP\Contracts\MiddlewareInterface;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class Pipeline implements RequestHandlerInterface
{
    /**
     * @param list<class-string<MiddlewareInterface>> $middleware
     */
    public function __construct(
        private Container $container,
        private array $middleware,
        private RequestHandlerInterface $destination,
    ) {
    }

    #[\Override]
    public function handle(Request $request): Response
    {
        if ($this->middleware === []) {
            return $this->destination->handle($request);
        }

        /** @var class-string<MiddlewareInterface> $class */
        $class = $this->middleware[0];
        $next = new self(
            $this->container,
            array_slice($this->middleware, 1),
            $this->destination,
        );

        /** @var MiddlewareInterface $instance */
        $instance = $this->container->make($class);

        return $instance->process($request, $next);
    }
}
