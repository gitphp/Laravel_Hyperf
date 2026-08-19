<?php

declare(strict_types=1);

namespace SunnyPHP\Contracts;

use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $next): Response;
}
