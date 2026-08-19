<?php

declare(strict_types=1);
/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */

namespace App\Http\Middleware;

use SunnyPHP\Contracts\MiddlewareInterface;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class RequestId implements MiddlewareInterface
{
    #[\Override]
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $id = $request->header('X-Request-Id') ?? bin2hex(random_bytes(8));
        $response = $next->handle($request->withAttribute('request_id', $id));

        return $response->header('X-Request-Id', $id);
    }
}
