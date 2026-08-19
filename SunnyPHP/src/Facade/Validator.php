<?php

declare(strict_types=1);

namespace SunnyPHP\Facade;

use SunnyPHP\Validation\Factory;

/**
 * @method static \SunnyPHP\Validation\Validator make(array $data, array $rules)
 * @method static array validate(array $data, array $rules)
 */
final class Validator extends Facade
{
    #[\Override]
    protected static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}
