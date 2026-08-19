<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use SunnyPHP\Cache\ArrayStore;
use SunnyPHP\Cache\CacheManager;
use SunnyPHP\Cache\FileStore;

final class CacheTest extends ApplicationTestCase
{
    public function testArrayStoreRememberAndExpire(): void
    {
        $store = new ArrayStore();
        $store->set('name', 'sunny', 60);
        $this->assertTrue($store->has('name'));
        $this->assertSame('sunny', $store->get('name'));

        $value = $store->remember('count', 60, fn (): int => 7);
        $this->assertSame(7, $value);
        $this->assertSame(7, $store->remember('count', 60, fn (): int => 99));

        $store->set('gone', 'x', -1);
        $this->assertFalse($store->has('gone'));
        $store->delete('name');
        $this->assertNull($store->get('name'));
    }

    public function testFileStoreRoundTrip(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sunnyphp-cache-' . bin2hex(random_bytes(4));
        $store = new FileStore($dir);
        $store->set('k', ['v' => 1], 60);
        $this->assertSame(['v' => 1], $store->get('k'));
        $store->clear();
        $this->assertFalse($store->has('k'));
    }

    public function testCacheManagerUsesArrayStore(): void
    {
        $app = $this->bootApp();
        $cache = $app->container->make(CacheManager::class);
        $cache->set('framework', 'SunnyPHP');
        $this->assertSame('SunnyPHP', $cache->get('framework'));
    }
}
