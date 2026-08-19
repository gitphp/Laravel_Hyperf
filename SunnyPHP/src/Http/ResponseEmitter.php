<?php

declare(strict_types=1);

namespace SunnyPHP\Http;

final class ResponseEmitter
{
    public function emit(Response $response): void
    {
        http_response_code($response->status());

        foreach ($response->headers() as $name => $value) {
            header("{$name}: {$value}", true);
        }

        foreach ($response->cookies() as $cookie) {
            $expires = gmdate('D, d M Y H:i:s', time() + $cookie['ttl']) . ' GMT';
            header(
                'Set-Cookie: ' . rawurlencode($cookie['name']) . '=' . rawurlencode($cookie['value'])
                . '; Path=/; HttpOnly; Expires=' . $expires,
                false,
            );
        }

        echo $response->body();
    }
}
