<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class TerminalHandler implements RequestHandlerInterface
{
    #[\Override]
    public function handle(Request $request): Response
    {
        Trace::$items[] = 'destination';

        return Response::text('ok');
    }
}
