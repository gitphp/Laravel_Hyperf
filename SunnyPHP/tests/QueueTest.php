<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use SunnyPHP\Config\Repository;
use SunnyPHP\Queue\QueueManager;
use SunnyPHP\Tests\Fixtures\RecordingJob;

final class QueueTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        RecordingJob::$handled = [];
    }

    public function testSyncQueueRunsImmediately(): void
    {
        $app = $this->bootApp();
        $queue = $app->container->make(QueueManager::class);
        $queue->push(RecordingJob::class, ['n' => 1]);

        $this->assertSame([['n' => 1]], RecordingJob::$handled);
    }

    public function testFileQueuePopAndRun(): void
    {
        $app = $this->bootApp();
        $app->container->get(Repository::class)->set('queue.default', 'file');
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sunnyphp-queue-' . bin2hex(random_bytes(4));
        $app->container->get(Repository::class)->set('queue.connections.file.path', $dir);

        $queue = $app->container->make(QueueManager::class);
        $id = $queue->push(RecordingJob::class, ['n' => 2]);
        $this->assertSame([], RecordingJob::$handled);

        $job = $queue->pop();
        $this->assertNotNull($job);
        $this->assertSame($id, $job->id);
        $queue->run($job);
        $this->assertSame([['n' => 2]], RecordingJob::$handled);
    }
}
