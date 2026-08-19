<?php

declare(strict_types=1);

namespace App\Jobs;
/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
use SunnyPHP\Queue\Job;

final class PingJob extends Job
{
    #[\Override]
    public function handle(): void
    {
        file_put_contents(
            dirname(__DIR__, 2) . '/storage/framework/queue/ping.job.result',
            (string) ($this->payload['message'] ?? 'ping'),
        );
    }
}
