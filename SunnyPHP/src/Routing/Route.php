<?php

declare(strict_types=1);

namespace SunnyPHP\Routing;

final class Route
{
    /**
     * @param list<string> $methods
     */
    public function __construct(
        public private(set) array $methods,
        public private(set) string $path,
        public private(set) mixed $handler,
    ) {
        $this->methods = array_map(strtoupper(...), $methods);
    }

    /** @return array<string, string>|null */
    public function matches(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        if (!in_array($method, $this->methods, true) && !in_array('*', $this->methods, true)) {
            return null;
        }

        if (!preg_match($this->compile(), $path, $matches)) {
            return null;
        }

        /** @var array<string, string> $params */
        $params = array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);

        return $params;
    }

    public function handlerLabel(): string
    {
        if ($this->handler instanceof \Closure) {
            return 'Closure';
        }

        if (is_array($this->handler) && isset($this->handler[0], $this->handler[1])) {
            $class = is_object($this->handler[0]) ? $this->handler[0]::class : (string) $this->handler[0];

            return $class . '@' . (string) $this->handler[1];
        }

        return is_string($this->handler) ? $this->handler : 'Callable';
    }

    private function compile(): string
    {
        $tokens = [];
        $index = 0;
        $replaced = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            function (array $match) use (&$tokens, &$index): string {
                $name = $match[1];
                $regex = $match[2] ?? '[^/]+';
                $token = '@@@' . $index . '@@@';
                $tokens[$token] = '(?P<' . $name . '>' . $regex . ')';
                $index++;

                return $token;
            },
            $this->path,
        );

        $quoted = preg_quote((string) $replaced, '#');
        foreach ($tokens as $token => $regex) {
            $quoted = str_replace(preg_quote($token, '#'), $regex, $quoted);
        }

        return '#^' . $quoted . '$#u';
    }
}
