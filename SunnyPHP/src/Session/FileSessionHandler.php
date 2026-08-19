<?php

declare(strict_types=1);

namespace SunnyPHP\Session;

final class FileSessionHandler implements SessionHandlerInterface
{
    public function __construct(
        private string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    #[\Override]
    public function read(string $id): array
    {
        $path = $this->path($id);
        if (!is_file($path)) {
            return [];
        }

        $data = unserialize((string) file_get_contents($path), ['allowed_classes' => true]);

        return is_array($data) ? $data : [];
    }

    #[\Override]
    public function write(string $id, array $data): void
    {
        file_put_contents($this->path($id), serialize($data), LOCK_EX);
    }

    #[\Override]
    public function destroy(string $id): void
    {
        $path = $this->path($id);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function path(string $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $id) . '.sess';
    }
}
