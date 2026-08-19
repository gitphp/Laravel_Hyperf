<?php

declare(strict_types=1);

namespace SunnyPHP\Http;

use SunnyPHP\Config\Repository;
use SunnyPHP\Container\Container;
use SunnyPHP\Exception\Handler;
use SunnyPHP\Middleware\Pipeline;
use SunnyPHP\Routing\Router;
use Throwable;

final class Kernel
{
    public function __construct(
        private Container $container,
        private Router $router,
        private Repository $config,
        private Handler $handler,
    ) {
    }

    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);

        try {
            /** @var list<class-string> $middleware */
            $middleware = $this->config->get('app.middleware', []);
            $pipeline = new Pipeline(
                $this->container,
                $middleware,
                new RouteDispatcher($this->container, $this->router),
            );

            return $pipeline->handle($request);
        } catch (Throwable $e) {
            return $this->handler->render($request, $e);
        }
    }
}
