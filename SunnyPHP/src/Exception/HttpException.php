<?php

declare(strict_types=1);

namespace SunnyPHP\Exception;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    public function __construct(
        public private(set) int $status,
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
