<?php

declare(strict_types=1);

namespace SunnyPHP\Auth;

use SunnyPHP\Contracts\MiddlewareInterface;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Exception\HttpException;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class Authenticate implements MiddlewareInterface
{
    public function __construct(
        private AuthManager $auth,
    ) {
    }

    #[\Override]
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        if ($this->auth->guest()) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        return $next->handle($request);
    }
}
