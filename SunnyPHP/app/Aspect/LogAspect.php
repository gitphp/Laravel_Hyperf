<?php

declare(strict_types=1);
/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */

namespace App\Aspect;

use Psr\Log\LoggerInterface;
use SunnyPHP\Aop\AspectInterface;
use SunnyPHP\Aop\Pointcut;
use SunnyPHP\Aop\ProceedingJoinPoint;
use App\Services\DemoService;

#[Pointcut(class: DemoService::class, method: '*')]
final class LogAspect implements AspectInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function process(ProceedingJoinPoint $joinPoint): mixed
    {
        $this->logger->info('aop.' . $joinPoint->target::class . '::' . $joinPoint->method);

        return $joinPoint->proceed();
    }
}
