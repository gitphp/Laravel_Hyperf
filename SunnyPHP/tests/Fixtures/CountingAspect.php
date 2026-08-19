<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

use SunnyPHP\Aop\AspectInterface;
use SunnyPHP\Aop\Pointcut;
use SunnyPHP\Aop\ProceedingJoinPoint;

#[Pointcut(class: Counter::class, method: 'add')]
final class CountingAspect implements AspectInterface
{
    public static int $calls = 0;

    #[\Override]
    public function process(ProceedingJoinPoint $joinPoint): mixed
    {
        self::$calls++;

        return $joinPoint->proceed();
    }
}
