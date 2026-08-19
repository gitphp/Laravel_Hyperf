<?php

declare(strict_types=1);

namespace SunnyPHP\Config;

final class Repository
{
    /** @param array<string, mixed> $items */
    public function __construct(
        private array $items = [],
    ) {
    }

    public function has(string $key): bool
    {
        return $this->lookup($key) !== self::missing();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->lookup($key);

        return $value === self::missing() ? $default : $value;
    }

    public function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $items = &$this->items;

        while (count($segments) > 1) {
            $segment = array_shift($segments);
            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }
            $items = &$items[$segment];
        }

        $items[array_shift($segments)] = $value;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    private function lookup(string $key): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return self::missing();
            }
            $value = $value[$segment];
        }

        return $value;
    }

    private static function missing(): object
    {
        static $missing;

        return $missing ??= new class {
        };
    }
}
