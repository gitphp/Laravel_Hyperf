<?php

declare(strict_types=1);

namespace SunnyPHP\Session;

use SunnyPHP\Contracts\MiddlewareInterface;
use SunnyPHP\Contracts\RequestHandlerInterface;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class StartSession implements MiddlewareInterface
{
    public function __construct(
        private SessionManager $session,
    ) {
    }

    #[\Override]
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $id = $request->cookie($this->session->cookieName());
        $this->session->start(is_string($id) ? $id : null);

        $response = $next->handle($request);
        $this->session->save();

        return $response->cookie(
            $this->session->cookieName(),
            $this->session->id(),
            $this->session->lifetime(),
        );
    }
}
