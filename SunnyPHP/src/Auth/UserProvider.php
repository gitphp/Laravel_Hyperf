<?php

declare(strict_types=1);

namespace SunnyPHP\Auth;

interface UserProvider
{
    public function retrieveById(int|string $id): ?Authenticatable;

    /** @param array<string, mixed> $credentials */
    public function retrieveByCredentials(array $credentials): ?Authenticatable;
}
