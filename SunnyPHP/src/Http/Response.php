<?php

declare(strict_types=1);

namespace SunnyPHP\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     * @param list<array{name: string, value: string, ttl: int}> $cookies
     */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        private array $headers = [],
        private array $cookies = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'application/json; charset=utf-8'] + $headers;
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($body, $status, $headers);
    }

    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'text/plain; charset=utf-8'] + $headers;

        return new self($body, $status, $headers);
    }

    public static function html(string $body, int $status = 200, array $headers = []): self
    {
        $headers = ['Content-Type' => 'text/html; charset=utf-8'] + $headers;

        return new self($body, $status, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function cookie(string $name, string $value, int $ttl = 7200): self
    {
        $clone = clone $this;
        $clone->cookies[] = ['name' => $name, 'value' => $value, 'ttl' => $ttl];

        return $clone;
    }

    /** @return list<array{name: string, value: string, ttl: int}> */
    public function cookies(): array
    {
        return $this->cookies;
    }
}
