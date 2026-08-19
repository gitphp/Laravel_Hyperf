<?php

declare(strict_types=1);

namespace SunnyPHP\Tests\Fixtures;

use SunnyPHP\Queue\Job;

final class RecordingJob extends Job
{
    /** @var list<array<string, mixed>> */
    public static array $handled = [];

    #[\Override]
    public function handle(): void
    {
        self::$handled[] = $this->payload;
    }
}
