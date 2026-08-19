<?php

declare(strict_types=1);

namespace SunnyPHP\Auth;

interface Authenticatable
{
    public function getAuthIdentifier(): int|string;

    public function getAuthPassword(): string;
}
