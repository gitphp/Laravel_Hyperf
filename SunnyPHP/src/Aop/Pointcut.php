<?php

declare(strict_types=1);

namespace SunnyPHP\Aop;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Pointcut
{
    public function __construct(
        public string $class,
        public string $method = '*',
    ) {
    }

    public function matches(string $class, string $method): bool
    {
        return $this->matchClass($class) && ($this->method === '*' || $this->method === $method);
    }

    private function matchClass(string $class): bool
    {
        if ($this->class === '*' || $this->class === $class) {
            return true;
        }

        if (str_ends_with($this->class, '*')) {
            $prefix = substr($this->class, 0, -1);

            return str_starts_with($class, $prefix);
        }

        return is_subclass_of($class, $this->class);
    }
}
