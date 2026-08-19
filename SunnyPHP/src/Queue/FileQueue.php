<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

final class FileQueue implements QueueDriver
{
    public function __construct(
        private string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    #[\Override]
    public function push(string $job, array $payload = [], int $delay = 0, string $queue = 'default'): string
    {
        return $this->later($delay, $job, $payload, $queue);
    }

    #[\Override]
    public function later(int $delay, string $job, array $payload = [], string $queue = 'default'): string
    {
        $id = bin2hex(random_bytes(8));
        $record = [
            'id' => $id,
            'queue' => $queue,
            'class' => $job,
            'payload' => $payload,
            'attempts' => 0,
            'available_at' => time() + $delay,
        ];

        file_put_contents($this->path($queue, $id), json_encode($record, JSON_THROW_ON_ERROR), LOCK_EX);

        return $id;
    }

    #[\Override]
    public function pop(string $queue = 'default'): ?QueuedJob
    {
        $now = time();
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . $queue . '-*.job') ?: [] as $file) {
            $record = json_decode((string) file_get_contents($file), true);
            if (!is_array($record) || (int) ($record['available_at'] ?? 0) > $now) {
                continue;
            }

            @unlink($file);

            return new QueuedJob(
                id: (string) $record['id'],
                class: (string) $record['class'],
                payload: is_array($record['payload'] ?? null) ? $record['payload'] : [],
                attempts: (int) ($record['attempts'] ?? 0) + 1,
                queue: $queue,
            );
        }

        return null;
    }

    private function path(string $queue, string $id): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $queue . '-' . $id . '.job';
    }
}
