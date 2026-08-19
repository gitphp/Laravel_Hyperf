<?php

declare(strict_types=1);

namespace SunnyPHP\Aop;

use ReflectionClass;
use SunnyPHP\Config\Repository;
use SunnyPHP\Container\Container;

final class AspectManager
{
    /** @var list<class-string<AspectInterface>> */
    private array $aspects;

    public function __construct(
        private Container $container,
        Repository $config,
    ) {
        /** @var list<class-string<AspectInterface>> $aspects */
        $aspects = $config->get('aop.aspects', []);
        $this->aspects = $aspects;
    }

    public function wrap(object $object): object
    {
        if ($object instanceof AopProxy || $object instanceof AspectInterface || $object instanceof self) {
            return $object;
        }

        $class = $object::class;
        $reflection = new ReflectionClass($class);
        if ($reflection->isFinal() || $reflection->isInternal() || $reflection->isAnonymous()) {
            return $object;
        }

        $matched = [];
        foreach ($this->aspects as $aspectClass) {
            if ($this->classMatches($aspectClass, $class)) {
                /** @var AspectInterface $aspect */
                $aspect = $this->container->make($aspectClass);
                $matched[] = $aspect;
            }
        }

        if ($matched === []) {
            return $object;
        }

        return ProxyFactory::create($object, $matched);
    }

    /** @param class-string<AspectInterface> $aspectClass */
    private function classMatches(string $aspectClass, string $class): bool
    {
        if (!class_exists($aspectClass)) {
            return false;
        }

        $attributes = (new ReflectionClass($aspectClass))->getAttributes(Pointcut::class);
        if ($attributes === []) {
            return false;
        }

        return array_any(
            $attributes,
            fn (\ReflectionAttribute $attribute): bool => $attribute->newInstance()->matches($class, '*')
                || array_any(
                    (new ReflectionClass($class))->getMethods(),
                    fn (\ReflectionMethod $method): bool => $attribute->newInstance()->matches($class, $method->getName()),
                ),
        );
    }
}
