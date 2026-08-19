<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Config\Repository;

final class ConfigTest extends TestCase
{
    public function testDotNotationGetSetHas(): void
    {
        $config = new Repository([
            'app' => [
                'name' => 'SunnyPHP',
                'debug' => true,
            ],
        ]);

        $this->assertTrue($config->has('app.debug'));
        $this->assertFalse($config->has('app.missing'));
        $this->assertTrue($config->get('app.debug'));
        $this->assertSame('SunnyPHP', $config->get('app.name'));
        $this->assertSame('fallback', $config->get('app.missing', 'fallback'));

        $config->set('app.debug', false);
        $this->assertFalse($config->get('app.debug'));

        $config->set('cache.driver', 'file');
        $this->assertSame('file', $config->get('cache.driver'));
        $this->assertSame(['name' => 'SunnyPHP', 'debug' => false], $config->get('app'));
    }
}
