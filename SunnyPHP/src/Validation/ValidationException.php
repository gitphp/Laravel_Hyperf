<?php

declare(strict_types=1);

namespace SunnyPHP\Validation;

use SunnyPHP\Exception\HttpException;

class ValidationException extends HttpException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(
        public private(set) array $errors,
        string $message = 'The given data was invalid.',
    ) {
        parent::__construct(422, $message);
    }
}
