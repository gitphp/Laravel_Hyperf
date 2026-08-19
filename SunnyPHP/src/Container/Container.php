<?php

declare(strict_types=1);

namespace SunnyPHP\Container;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use SunnyPHP\Aop\AspectManager;

final class Container implements ContainerInterface
{
    private ?AspectManager $aspectManager = null;

    /** @var array<string, array{concrete: callable|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, true> */
    private array $buildStack = [];

    public function bind(string $id, callable|string $concrete, bool $shared = false): void
    {
        unset($this->instances[$id]);
        $this->bindings[$id] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    public function singleton(string $id, callable|string $concrete): void
    {
        $this->bind($id, $concrete, true);
    }

    public function instance(string $id, mixed $instance): void
    {
        unset($this->bindings[$id]);
        $this->instances[$id] = $instance;
    }

    #[\Override]
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service [{$id}] not found.");
        }

        return $this->make($id);
    }

    #[\Override]
    public function has(string $id): bool
    {
        if (isset($this->instances[$id]) || isset($this->bindings[$id])) {
            return true;
        }

        return class_exists($id) && (new ReflectionClass($id))->isInstantiable();
    }

    public function enableAop(AspectManager $manager): void
    {
        $this->aspectManager = $manager;
    }

    public function make(string $id, array $parameters = []): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        $binding = $this->bindings[$id] ?? null;
        $concrete = $binding['concrete'] ?? $id;
        $shared = $binding['shared'] ?? false;

        $object = $this->resolve($id, $concrete, $parameters);

        if (is_object($object)) {
            $object = $this->applyAop($object);
        }

        if ($shared) {
            $this->instances[$id] = $object;
        }

        return $object;
    }

    private function applyAop(object $object): object
    {
        return $this->aspectManager?->wrap($object) ?? $object;
    }

    private function resolve(string $id, callable|string $concrete, array $parameters): mixed
    {
        if (is_callable($concrete) && !is_string($concrete)) {
            return $concrete($this, $parameters);
        }

        $class = is_string($concrete) ? $concrete : $id;

        return $this->build($class, $parameters);
    }

    private function build(string $class, array $parameters): object
    {
        if (isset($this->buildStack[$class])) {
            $chain = implode(' -> ', [...array_keys($this->buildStack), $class]);
            throw new ContainerException("Circular dependency detected: {$chain}");
        }

        if (!class_exists($class)) {
            throw new NotFoundException("Class [{$class}] does not exist.");
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $this->buildStack[$class] = true;

        try {
            $dependencies = array_map(
                fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($class, $parameter, $parameters),
                $constructor->getParameters(),
            );
        } finally {
            unset($this->buildStack[$class]);
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter, array $parameters): mixed
    {
        $name = $parameter->getName();
        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        throw new ContainerException("Cannot resolve parameter \${$name} of {$class}.");
    }
}
