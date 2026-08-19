<?php

declare(strict_types=1);

namespace SunnyPHP\Http;

use SunnyPHP\Container\Container;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Exception\HttpException;
use SunnyPHP\Routing\Router;

final class RouteDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private Container $container,
        private Router $router,
    ) {
    }

    #[\Override]
    public function handle(Request $request): Response
    {
        $matched = $this->router->matchRequest($request);
        if ($matched === null) {
            throw new HttpException(404, 'Not Found');
        }

        foreach ($matched->parameters as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $result = $this->invoke($matched->route->handler, $request);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::text((string) $result);
    }

    private function invoke(mixed $handler, Request $request): mixed
    {
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            $class = $handler[0];
            $method = (string) $handler[1];
            $controller = is_object($class) ? $class : $this->container->make((string) $class);

            return $controller->{$method}($request);
        }

        if (is_callable($handler)) {
            return $handler($request);
        }

        throw new HttpException(500, 'Invalid route handler.');
    }
}
