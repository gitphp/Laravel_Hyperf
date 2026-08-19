<?php

declare(strict_types=1);

namespace SunnyPHP\Validation;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|list<string>> $rules
     */
    public function __construct(
        private array $data,
        private array $rules,
    ) {
    }

    /** @return array<string, mixed> */
    public function validate(): array
    {
        if (!$this->passes()) {
            throw new ValidationException($this->errors);
        }

        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;
            $present = array_key_exists($field, $this->data) && $value !== null && $value !== '';

            foreach ($rules as $rule) {
                $this->apply($field, $value, $present, (string) $rule);
            }
        }

        return $this->errors === [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function apply(string $field, mixed $value, bool $present, string $rule): void
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
        $name = strtolower((string) $name);

        if ($name !== 'required' && !$present) {
            return;
        }

        match ($name) {
            'required' => $present ?: $this->fail($field, "The {$field} field is required."),
            'string' => is_string($value) ?: $this->fail($field, "The {$field} field must be a string."),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ?: $this->fail($field, "The {$field} field must be an integer."),
            'numeric' => is_numeric($value) ?: $this->fail($field, "The {$field} field must be numeric."),
            'boolean' => $this->isBoolean($value) ?: $this->fail($field, "The {$field} field must be a boolean."),
            'array' => is_array($value) ?: $this->fail($field, "The {$field} field must be an array."),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false ?: $this->fail($field, "The {$field} field must be a valid email."),
            'min' => $this->checkMin($field, $value, (int) $parameter),
            'max' => $this->checkMax($field, $value, (int) $parameter),
            'confirmed' => ($this->data[$field . '_confirmation'] ?? null) === $value
                ?: $this->fail($field, "The {$field} confirmation does not match."),
            'same' => ($this->data[(string) $parameter] ?? null) === $value
                ?: $this->fail($field, "The {$field} field must match {$parameter}."),
            'in' => in_array($value, explode(',', (string) $parameter), false)
                ?: $this->fail($field, "The {$field} field is invalid."),
            default => null,
        };
    }

    private function checkMin(string $field, mixed $value, int $min): void
    {
        if (is_numeric($value) && !is_string($value)) {
            if ((float) $value < $min) {
                $this->fail($field, "The {$field} field must be at least {$min}.");
            }

            return;
        }

        $length = is_array($value) ? count($value) : strlen((string) $value);
        if ($length < $min) {
            $this->fail($field, "The {$field} field must be at least {$min} characters.");
        }
    }

    private function checkMax(string $field, mixed $value, int $max): void
    {
        if (is_numeric($value) && !is_string($value)) {
            if ((float) $value > $max) {
                $this->fail($field, "The {$field} field must not be greater than {$max}.");
            }

            return;
        }

        $length = is_array($value) ? count($value) : strlen((string) $value);
        if ($length > $max) {
            $this->fail($field, "The {$field} field must not be greater than {$max} characters.");
        }
    }

    private function isBoolean(mixed $value): bool
    {
        return is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1';
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field][] = $message;

        return false;
    }
}
