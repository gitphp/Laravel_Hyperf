<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Application;
use SunnyPHP\Config\Repository;
use SunnyPHP\Http\Kernel;
use SunnyPHP\Http\Request;
use SunnyPHP\Routing\Router;

final class HttpKernelTest extends TestCase
{
    private Application $app;

    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->app = Application::create(dirname(__DIR__));
        $this->app->boot();
        $this->kernel = $this->app->container->make(Kernel::class);
    }

    public function testAopDemoRoute(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/demo/aop'));
        $payload = json_decode($response->body(), true);

        $this->assertSame(200, $response->status());
        $this->assertSame('pong', $payload['result']);
    }

    public function testHomeReturnsJson(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/'));
        $payload = json_decode($response->body(), true);

        $this->assertSame(200, $response->status());
        $this->assertSame('SunnyPHP', $payload['name']);
        $this->assertSame('Hello, World', $payload['message']);
        $this->assertNotEmpty($payload['request_id']);
        $this->assertArrayHasKey('X-Request-Id', $response->headers());
    }

    public function testHelloRouteUsesPathParameter(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/hello/sunny'));
        $payload = json_decode($response->body(), true);

        $this->assertSame(200, $response->status());
        $this->assertSame('Hello, sunny', $payload['message']);
    }

    public function testUnknownRouteReturns404(): void
    {
        $response = $this->kernel->handle(Request::create('GET', '/missing'));
        $payload = json_decode($response->body(), true);

        $this->assertSame(404, $response->status());
        $this->assertSame('Not Found', $payload['error']);
    }

    public function testUnhandledExceptionReturns500(): void
    {
        /** @var Router $router */
        $router = $this->app->container->get(Router::class);
        $router->get('/boom', static function (): never {
            throw new \RuntimeException('explode');
        });

        $this->app->container->get(Repository::class)->set('app.debug', true);
        $debugResponse = $this->kernel->handle(Request::create('GET', '/boom'));
        $debugPayload = json_decode($debugResponse->body(), true);

        $this->assertSame(500, $debugResponse->status());
        $this->assertSame('explode', $debugPayload['message']);

        $this->app->container->get(Repository::class)->set('app.debug', false);
        $prodResponse = $this->kernel->handle(Request::create('GET', '/boom'));
        $prodPayload = json_decode($prodResponse->body(), true);

        $this->assertSame(500, $prodResponse->status());
        $this->assertSame('Internal Server Error', $prodPayload['error']);
    }
}
