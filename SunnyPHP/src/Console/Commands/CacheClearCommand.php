<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Cache\CacheManager;
use SunnyPHP\Console\Command;

final class CacheClearCommand extends Command
{
    public function __construct(
        private CacheManager $cache,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'cache:clear';
    }

    #[\Override]
    public function description(): string
    {
        return 'Clear the application cache';
    }

    #[\Override]
    public function handle(array $args): int
    {
        $this->cache->clear();
        echo "Cache cleared.\n";

        return 0;
    }
}
