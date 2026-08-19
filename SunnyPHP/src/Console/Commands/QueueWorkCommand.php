<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Console\Command;
use SunnyPHP\Queue\QueueManager;

final class QueueWorkCommand extends Command
{
    public function __construct(
        private QueueManager $queue,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'queue:work';
    }

    #[\Override]
    public function description(): string
    {
        return 'Process queued jobs (use --once to run a single job)';
    }

    #[\Override]
    public function handle(array $args): int
    {
        $once = in_array('--once', $args, true);
        $queue = 'default';

        do {
            $job = $this->queue->pop($queue);
            if ($job === null) {
                if ($once) {
                    echo "No jobs available.\n";

                    return 0;
                }
                sleep(1);
                continue;
            }

            echo "Processing {$job->class}#{$job->id}\n";
            $this->queue->run($job);
            echo "Processed {$job->class}#{$job->id}\n";
        } while (!$once);

        return 0;
    }
}
