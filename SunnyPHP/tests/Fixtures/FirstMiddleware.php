<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

use SunnyPHP\Contracts\MiddlewareInterface;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class FirstMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        Trace::$items[] = 'first:before';
        $response = $next->handle($request);
        Trace::$items[] = 'first:after';

        return $response;
    }
}
