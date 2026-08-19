<?php

declare(strict_types=1);

namespace SunnyPHP\Session;

final class ArraySessionHandler implements SessionHandlerInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $storage = [];

    #[\Override]
    public function read(string $id): array
    {
        return $this->storage[$id] ?? [];
    }

    #[\Override]
    public function write(string $id, array $data): void
    {
        $this->storage[$id] = $data;
    }

    #[\Override]
    public function destroy(string $id): void
    {
        unset($this->storage[$id]);
    }
}
