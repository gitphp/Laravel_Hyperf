<?php

declare(strict_types=1);

namespace SunnyPHP\Auth;

use SunnyPHP\Config\Repository;
use SunnyPHP\Session\SessionManager;

final class AuthManager
{
    private ?Authenticatable $user = null;

    private bool $resolved = false;

    public function __construct(
        private SessionManager $session,
        private UserProvider $provider,
        private Repository $config,
    ) {
    }

    /** @param array<string, mixed> $credentials */
    public function attempt(array $credentials): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        $password = (string) ($credentials[$this->passwordColumn()] ?? '');

        if ($user === null || !password_verify($password, $user->getAuthPassword())) {
            return false;
        }

        $this->login($user);

        return true;
    }

    public function login(Authenticatable $user): void
    {
        $this->session->set($this->sessionKey(), $user->getAuthIdentifier());
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->forget($this->sessionKey());
        $this->user = null;
        $this->resolved = true;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function id(): int|string|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function user(): ?Authenticatable
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $id = $this->session->get($this->sessionKey());
        if ($id === null) {
            $this->user = null;

            return null;
        }

        $this->user = $this->provider->retrieveById($id);

        return $this->user;
    }

    private function sessionKey(): string
    {
        return (string) $this->config->get('auth.session_key', 'login_id');
    }

    private function passwordColumn(): string
    {
        return (string) $this->config->get('auth.password', 'password');
    }
}
