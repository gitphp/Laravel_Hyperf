<?php

declare(strict_types=1);

namespace SunnyPHP\Aop;

interface AspectInterface
{
    public function process(ProceedingJoinPoint $joinPoint): mixed;
}
