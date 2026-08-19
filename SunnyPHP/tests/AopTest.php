<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Aop\AopProxy;
use SunnyPHP\Aop\AspectManager;
use SunnyPHP\Config\Repository;
use SunnyPHP\Container\Container;
use SunnyPHP\Tests\Fixtures\Counter;
use SunnyPHP\Tests\Fixtures\CountingAspect;

final class AopTest extends TestCase
{
    public function testAspectInterceptsPublicMethod(): void
    {
        CountingAspect::$calls = 0;

        $container = new Container();
        $config = new Repository([
            'aop' => ['aspects' => [CountingAspect::class]],
        ]);
        $container->instance(Repository::class, $config);
        $container->instance(Container::class, $container);
        $manager = new AspectManager($container, $config);
        $container->enableAop($manager);

        $counter = $container->make(Counter::class);

        $this->assertInstanceOf(Counter::class, $counter);
        $this->assertInstanceOf(AopProxy::class, $counter);
        $this->assertSame(4, $counter->add(3));
        $this->assertSame(1, CountingAspect::$calls);
    }
}
