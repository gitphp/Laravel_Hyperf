<?php

declare(strict_types=1);

namespace SunnyPHP\Http;

final class Request
{
    /** @var array<string, mixed> */
    private array $jsonCache = [];

    private bool $jsonDecoded = false;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public private(set) array $query = [],
        public private(set) array $request = [],
        public private(set) array $server = [],
        public private(set) array $cookies = [],
        public private(set) array $files = [],
        public private(set) string $body = '',
        private array $attributes = [],
    ) {
    }

    public string $method {
        get => strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public string $path {
        get {
            $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
            $path = parse_url($uri, PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? rawurldecode($path) : '/';
            $normalized = '/' . trim($path, '/');

            return $normalized === '/' ? '/' : $normalized;
        }
    }

    public static function capture(): self
    {
        return new self(
            query: $_GET,
            request: $_POST,
            server: $_SERVER,
            cookies: $_COOKIE,
            files: $_FILES,
            body: file_get_contents('php://input') ?: '',
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $request
     * @param array<string, mixed> $server
     */
    public static function create(
        string $method,
        string $uri,
        array $query = [],
        array $request = [],
        array $server = [],
        string $body = '',
        array $cookies = [],
    ): self {
        $server = array_merge([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
        ], $server);

        return new self(
            query: $query,
            request: $request,
            server: $server,
            cookies: $cookies,
            body: $body,
        );
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->request)) {
            return $this->request[$key];
        }

        $json = $this->json();
        if (is_array($json) && array_key_exists($key, $json)) {
            return $json[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function json(): ?array
    {
        if ($this->jsonDecoded) {
            return $this->jsonCache === [] && $this->body === '' ? null : $this->jsonCache;
        }

        $this->jsonDecoded = true;
        $contentType = $this->header('Content-Type') ?? '';
        if ($this->body === '' || !str_contains(strtolower($contentType), 'json')) {
            $this->jsonCache = [];

            return null;
        }

        try {
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->jsonCache = [];

            return null;
        }

        $this->jsonCache = is_array($decoded) ? $decoded : [];

        return $this->jsonCache === [] && !is_array($decoded) ? null : $this->jsonCache;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($this->server[$key])) {
            return (string) $this->server[$key];
        }

        if (strcasecmp($name, 'Content-Type') === 0) {
            return isset($this->server['CONTENT_TYPE']) ? (string) $this->server['CONTENT_TYPE'] : null;
        }

        if (strcasecmp($name, 'Content-Length') === 0) {
            return isset($this->server['CONTENT_LENGTH']) ? (string) $this->server['CONTENT_LENGTH'] : null;
        }

        return null;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;

        return $clone;
    }
}
