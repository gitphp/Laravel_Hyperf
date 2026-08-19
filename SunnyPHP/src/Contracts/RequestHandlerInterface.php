<?php

declare(strict_types=1);

namespace SunnyPHP\Contracts;

use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}
