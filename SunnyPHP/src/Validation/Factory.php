<?php

declare(strict_types=1);

namespace SunnyPHP\Validation;

final class Factory
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     */
    public function make(array $data, array $rules): Validator
    {
        return new Validator($data, $rules);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     * @return array<string, mixed>
     */
    public function validate(array $data, array $rules): array
    {
        return $this->make($data, $rules)->validate();
    }
}
