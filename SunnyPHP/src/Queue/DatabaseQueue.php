<?php

declare(strict_types=1);

namespace SunnyPHP\Queue;

use SunnyPHP\Database\DatabaseManager;

final class DatabaseQueue implements QueueDriver
{
    public function __construct(
        private DatabaseManager $db,
    ) {
    }

    #[\Override]
    public function push(string $job, array $payload = [], int $delay = 0, string $queue = 'default'): string
    {
        return $this->later($delay, $job, $payload, $queue);
    }

    #[\Override]
    public function later(int $delay, string $job, array $payload = [], string $queue = 'default'): string
    {
        $id = $this->db->table('jobs')->insertGetId([
            'queue' => $queue,
            'payload' => json_encode([
                'class' => $job,
                'data' => $payload,
            ], JSON_THROW_ON_ERROR),
            'attempts' => 0,
            'available_at' => time() + $delay,
            'created_at' => time(),
        ]);

        return $id;
    }

    #[\Override]
    public function pop(string $queue = 'default'): ?QueuedJob
    {
        $row = $this->db->select(
            'SELECT * FROM "jobs" WHERE "queue" = ? AND "available_at" <= ? ORDER BY "id" ASC LIMIT 1',
            [$queue, time()],
        )[0] ?? null;

        if ($row === null) {
            return null;
        }

        $this->db->table('jobs')->where('id', $row['id'])->delete();
        $decoded = json_decode((string) $row['payload'], true) ?: [];

        return new QueuedJob(
            id: (string) $row['id'],
            class: (string) ($decoded['class'] ?? ''),
            payload: is_array($decoded['data'] ?? null) ? $decoded['data'] : [],
            attempts: (int) $row['attempts'] + 1,
            queue: $queue,
        );
    }
}
