<?php

declare(strict_types=1);

namespace SunnyPHP\Session;

use InvalidArgumentException;
use SunnyPHP\Application;
use SunnyPHP\Config\Repository;

final class SessionManager
{
    private ?string $id = null;

    /** @var array<string, mixed> */
    private array $data = [];

    private bool $started = false;

    private SessionHandlerInterface $handler;

    public function __construct(
        private Repository $config,
        Application $app,
    ) {
        $this->handler = $this->resolveHandler($app);
    }

    public function cookieName(): string
    {
        return (string) $this->config->get('session.cookie', 'sunny_session');
    }

    public function lifetime(): int
    {
        return (int) $this->config->get('session.lifetime', 7200);
    }

    public function start(?string $id = null): void
    {
        $this->id = $this->isValidId($id) ? $id : bin2hex(random_bytes(16));
        $this->data = $this->handler->read($this->id);
        $this->started = true;
    }

    public function id(): string
    {
        $this->ensureStarted();

        return (string) $this->id;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();

        return array_key_exists($key, $this->data);
    }

    public function forget(string $key): void
    {
        $this->ensureStarted();
        unset($this->data[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->set($key, $value);
        $flash = $this->get('_flash', []);
        $flash[] = $key;
        $this->data['_flash'] = $flash;
    }

    public function regenerate(): string
    {
        $this->ensureStarted();
        $old = (string) $this->id;
        $this->id = bin2hex(random_bytes(16));
        $this->handler->destroy($old);

        return $this->id;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $this->ensureStarted();

        return $this->data;
    }

    public function save(): void
    {
        if (!$this->started) {
            return;
        }

        $old = $this->data['_flash_old'] ?? [];
        if (is_array($old)) {
            foreach ($old as $key) {
                unset($this->data[$key]);
            }
        }
        $this->data['_flash_old'] = $this->data['_flash'] ?? [];
        $this->data['_flash'] = [];

        $this->handler->write((string) $this->id, $this->data);
    }

    public function invalidate(): void
    {
        $this->ensureStarted();
        $this->handler->destroy((string) $this->id);
        $this->data = [];
        $this->id = bin2hex(random_bytes(16));
    }

    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    private function isValidId(?string $id): bool
    {
        return is_string($id) && preg_match('/^[a-f0-9]{32}$/', $id) === 1;
    }

    private function resolveHandler(Application $app): SessionHandlerInterface
    {
        $driver = (string) $this->config->get('session.driver', 'file');

        return match ($driver) {
            'array' => new ArraySessionHandler(),
            'file' => new FileSessionHandler(
                (string) $this->config->get('session.path', $app->path('storage/framework/sessions')),
            ),
            default => throw new InvalidArgumentException("Unsupported session driver [{$driver}]."),
        };
    }
}
