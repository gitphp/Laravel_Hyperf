<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Container\Container;
use SunnyPHP\Http\Request;
use SunnyPHP\Middleware\Pipeline;
use SunnyPHP\Tests\Fixtures\FirstMiddleware;
use SunnyPHP\Tests\Fixtures\SecondMiddleware;
use SunnyPHP\Tests\Fixtures\TerminalHandler;
use SunnyPHP\Tests\Fixtures\Trace;

final class MiddlewarePipelineTest extends TestCase
{
    protected function setUp(): void
    {
        Trace::$items = [];
    }

    public function testMiddlewareRunsInOrder(): void
    {
        $pipeline = new Pipeline(
            new Container(),
            [FirstMiddleware::class, SecondMiddleware::class],
            new TerminalHandler(),
        );

        $response = $pipeline->handle(Request::create('GET', '/'));

        $this->assertSame('ok', $response->body());
        $this->assertSame(
            ['first:before', 'second:before', 'destination', 'second:after', 'first:after'],
            Trace::$items,
        );
    }

    public function testEmptyPipelineHitsDestination(): void
    {
        $pipeline = new Pipeline(new Container(), [], new TerminalHandler());
        $response = $pipeline->handle(Request::create('GET', '/'));

        $this->assertSame('ok', $response->body());
        $this->assertSame(['destination'], Trace::$items);
    }
}
