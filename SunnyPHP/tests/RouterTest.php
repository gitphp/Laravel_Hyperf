<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;
use SunnyPHP\Routing\Router;

final class RouterTest extends TestCase
{
    public function testMatchStaticGet(): void
    {
        $router = new Router();
        $router->get('/', fn (): Response => Response::text('home'));

        $matched = $router->matchRequest(Request::create('GET', '/'));

        $this->assertNotNull($matched);
        $this->assertSame('/', $matched->route->path);
        $this->assertSame([], $matched->parameters);
    }

    public function testMatchNamedParameter(): void
    {
        $router = new Router();
        $router->get('/hello/{name}', fn (): Response => Response::text('ok'));

        $matched = $router->matchRequest(Request::create('GET', '/hello/sunny'));

        $this->assertNotNull($matched);
        $this->assertSame(['name' => 'sunny'], $matched->parameters);
    }

    public function testMatchRegexParameter(): void
    {
        $router = new Router();
        $router->get('/users/{id:\d+}', fn (): Response => Response::text('ok'));

        $matched = $router->matchRequest(Request::create('GET', '/users/12'));
        $this->assertNotNull($matched);
        $this->assertSame(['id' => '12'], $matched->parameters);

        $this->assertNull($router->matchRequest(Request::create('GET', '/users/abc')));
    }

    public function testMethodMismatchReturnsNull(): void
    {
        $router = new Router();
        $router->get('/only-get', fn (): Response => Response::text('ok'));

        $this->assertNull($router->matchRequest(Request::create('POST', '/only-get')));
        $this->assertTrue($router->hasMethod('GET'));
        $this->assertFalse($router->hasMethod('DELETE'));
    }

    public function testUnknownPathReturnsNull(): void
    {
        $router = new Router();
        $router->get('/', fn (): Response => Response::text('home'));

        $this->assertNull($router->matchRequest(Request::create('GET', '/missing')));
    }
}
