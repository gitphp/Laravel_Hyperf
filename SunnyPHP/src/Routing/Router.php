<?php

declare(strict_types=1);

namespace SunnyPHP\Routing;

use SunnyPHP\Http\Request;

final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): Route
    {
        return $this->add(['GET'], $path, $handler);
    }

    public function post(string $path, callable|array $handler): Route
    {
        return $this->add(['POST'], $path, $handler);
    }

    public function put(string $path, callable|array $handler): Route
    {
        return $this->add(['PUT'], $path, $handler);
    }

    public function patch(string $path, callable|array $handler): Route
    {
        return $this->add(['PATCH'], $path, $handler);
    }

    public function delete(string $path, callable|array $handler): Route
    {
        return $this->add(['DELETE'], $path, $handler);
    }

    /** @param list<string> $methods */
    public function match(array $methods, string $path, callable|array $handler): Route
    {
        return $this->add($methods, $path, $handler);
    }

    public function any(string $path, callable|array $handler): Route
    {
        return $this->add(['*'], $path, $handler);
    }

    public function matchRequest(Request $request): ?MatchedRoute
    {
        $method = $request->method;
        $path = $request->path;

        $route = array_find(
            $this->routes,
            fn (Route $route): bool => $route->matches($method, $path) !== null,
        );

        if ($route === null) {
            return null;
        }

        $parameters = $route->matches($method, $path) ?? [];

        return new MatchedRoute($route, $parameters);
    }

    public function hasMethod(string $method): bool
    {
        return array_any(
            $this->routes,
            fn (Route $route): bool => in_array(strtoupper($method), $route->methods, true)
                || in_array('*', $route->methods, true),
        );
    }

    /** @return list<Route> */
    public function routes(): array
    {
        return $this->routes;
    }

    /** @param list<string> $methods */
    private function add(array $methods, string $path, callable|array $handler): Route
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        } else {
            $path = '/';
        }

        $route = new Route($methods, $path, $handler);
        $this->routes[] = $route;

        return $route;
    }
}
