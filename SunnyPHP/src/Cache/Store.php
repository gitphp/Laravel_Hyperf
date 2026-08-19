<?php

declare(strict_types=1);

namespace SunnyPHP\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

interface Store extends PsrCacheInterface
{
    public function remember(string $key, int|DateInterval|null $ttl, callable $callback): mixed;
}
