<?php

declare(strict_types=1);

namespace SunnyPHP\Aop;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

final class ProxyFactory
{
    /**
     * @param list<AspectInterface> $aspects
     */
    public static function create(object $target, array $aspects): object
    {
        $class = $target::class;
        $proxy = 'SunnyAop_' . str_replace('\\', '_', $class);
        if (!class_exists($proxy, false)) {
            eval(self::generate($class, $proxy));
        }

        return new $proxy($target, $aspects);
    }

    /**
     * @param list<AspectInterface> $aspects
     * @param array<int|string, mixed> $arguments
     */
    public static function invoke(object $target, array $aspects, string $method, array $arguments): mixed
    {
        $applicable = array_values(array_filter(
            $aspects,
            fn (AspectInterface $aspect): bool => self::aspectMatches($aspect, $target::class, $method),
        ));

        $call = static fn (array $arguments): mixed => $target->{$method}(...$arguments);

        foreach (array_reverse($applicable) as $aspect) {
            $previous = $call;
            $call = static function (array $arguments) use ($aspect, $target, $method, $previous): mixed {
                return $aspect->process(new ProceedingJoinPoint($target, $method, $arguments, $previous));
            };
        }

        return $call($arguments);
    }

    private static function aspectMatches(AspectInterface $aspect, string $class, string $method): bool
    {
        $attributes = (new ReflectionClass($aspect))->getAttributes(Pointcut::class);
        if ($attributes === []) {
            return false;
        }

        return array_any(
            $attributes,
            fn (\ReflectionAttribute $attribute): bool => $attribute->newInstance()->matches($class, $method),
        );
    }

    private static function generate(string $class, string $proxy): string
    {
        $reflection = new ReflectionClass($class);
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->isConstructor()
                || $method->isDestructor()
                || $method->isStatic()
                || $method->isFinal()
                || $method->isAbstract()
            ) {
                continue;
            }

            $params = [];
            foreach ($method->getParameters() as $parameter) {
                $item = '';
                if ($parameter->hasType()) {
                    $item .= self::stringifyType($parameter->getType()) . ' ';
                }
                if ($parameter->isPassedByReference()) {
                    $item .= '&';
                }
                if ($parameter->isVariadic()) {
                    $item .= '...';
                }
                $item .= '$' . $parameter->getName();
                if ($parameter->isDefaultValueAvailable()) {
                    $item .= ' = ' . var_export($parameter->getDefaultValue(), true);
                }
                $params[] = $item;
            }

            $return = $method->hasReturnType() ? ': ' . self::stringifyType($method->getReturnType()) : '';
            $isVoid = $method->getReturnType() instanceof ReflectionNamedType
                && $method->getReturnType()->getName() === 'void';
            $body = $isVoid
                ? '\\' . self::class . '::invoke($this->__sunnyTarget, $this->__sunnyAspects, \'' . $method->getName() . '\', func_get_args());'
                : 'return \\' . self::class . '::invoke($this->__sunnyTarget, $this->__sunnyAspects, \'' . $method->getName() . '\', func_get_args());';

            $methods[] = '    public function ' . $method->getName() . '(' . implode(', ', $params) . ')' . $return . "\n    {\n        {$body}\n    }";
        }

        return 'class ' . $proxy . ' extends \\' . $class . ' implements \\' . AopProxy::class . "\n{\n"
            . "    private object \$__sunnyTarget;\n"
            . "    private array \$__sunnyAspects;\n"
            . "    public function __construct(object \$target, array \$aspects)\n    {\n"
            . "        \$this->__sunnyTarget = \$target;\n"
            . "        \$this->__sunnyAspects = \$aspects;\n    }\n"
            . implode("\n\n", $methods)
            . "\n}\n";
    }

    private static function stringifyType(\ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            $name = $type->isBuiltin() ? $name : '\\' . $name;

            return ($type->allowsNull() && $name !== 'mixed' && $name !== 'null' ? '?' : '') . $name;
        }

        if ($type instanceof ReflectionUnionType) {
            $parts = array_map(self::stringifyType(...), $type->getTypes());

            return implode('|', $parts);
        }

        return (string) $type;
    }
}
