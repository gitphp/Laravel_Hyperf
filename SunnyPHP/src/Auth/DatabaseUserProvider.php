<?php

declare(strict_types=1);

namespace SunnyPHP\Auth;

use SunnyPHP\Config\Repository;
use SunnyPHP\Database\DatabaseManager;
use SunnyPHP\Database\Model;

final class DatabaseUserProvider implements UserProvider
{
    public function __construct(
        private DatabaseManager $db,
        private Repository $config,
    ) {
    }

    #[\Override]
    public function retrieveById(int|string $id): ?Authenticatable
    {
        $row = $this->db->table($this->table())->where($this->idColumn(), $id)->first();

        return $row === null ? null : $this->hydrate($row);
    }

    #[\Override]
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $query = $this->db->table($this->table());
        $used = false;

        foreach ($credentials as $key => $value) {
            if ($key === $this->passwordColumn()) {
                continue;
            }
            $query->where($key, $value);
            $used = true;
        }

        if (!$used) {
            return null;
        }

        $row = $query->first();

        return $row === null ? null : $this->hydrate($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Authenticatable
    {
        $class = (string) $this->config->get('auth.model');

        if (is_subclass_of($class, Model::class)) {
            /** @var class-string<Model&Authenticatable> $class */
            $user = $class::hydrate($row);
        } else {
            $user = new $class($row);
        }

        if (!$user instanceof Authenticatable) {
            throw new \RuntimeException("Auth model [{$class}] must implement Authenticatable.");
        }

        return $user;
    }

    private function table(): string
    {
        return (string) $this->config->get('auth.table', 'users');
    }

    private function idColumn(): string
    {
        return (string) $this->config->get('auth.id', 'id');
    }

    private function passwordColumn(): string
    {
        return (string) $this->config->get('auth.password', 'password');
    }
}
