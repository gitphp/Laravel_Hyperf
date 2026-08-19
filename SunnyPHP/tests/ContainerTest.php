<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Container\Container;
use SunnyPHP\Container\ContainerException;
use SunnyPHP\Container\NotFoundException;
use SunnyPHP\Tests\Fixtures\Clock;
use SunnyPHP\Tests\Fixtures\Greeter;

final class ContainerTest extends TestCase
{
    public function testBindAndGet(): void
    {
        $container = new Container();
        $container->bind(Clock::class, Clock::class);

        $clock = $container->get(Clock::class);

        $this->assertInstanceOf(Clock::class, $clock);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(Clock::class, Clock::class);

        $this->assertSame($container->get(Clock::class), $container->get(Clock::class));
    }

    public function testInstanceBinding(): void
    {
        $container = new Container();
        $clock = new Clock();
        $container->instance(Clock::class, $clock);

        $this->assertSame($clock, $container->get(Clock::class));
    }

    public function testAutowireConstructor(): void
    {
        $container = new Container();
        $greeter = $container->make(Greeter::class);

        $this->assertInstanceOf(Greeter::class, $greeter);
        $this->assertSame('hello@now', $greeter->greet());
    }

    public function testNotFound(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $container->get('MissingService');
    }

    public function testHasIsFalseForUnknownId(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('MissingService'));
        $this->assertTrue($container->has(Clock::class));
    }

    public function testClosureBinding(): void
    {
        $container = new Container();
        $container->bind(Clock::class, fn (): Clock => new Clock());

        $this->assertInstanceOf(Clock::class, $container->make(Clock::class));
    }

    public function testUnresolvedParameter(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $container->make(NeedsString::class);
    }
}

final class NeedsString
{
    public function __construct(public string $value)
    {
    }
}
