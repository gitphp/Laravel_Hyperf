<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use SunnyPHP\Facade\Cache;
use SunnyPHP\Facade\Config;
use SunnyPHP\Facade\DB;
use SunnyPHP\Facade\Validator;

final class FacadeTest extends ApplicationTestCase
{
    public function testFacadesResolveThroughContainer(): void
    {
        $app = $this->bootApp();
        $this->migrate($app);

        $this->assertTrue(Config::has('app.name'));
        $this->assertSame('SunnyPHP', Config::get('app.name'));

        Cache::set('via-facade', 42);
        $this->assertSame(42, Cache::get('via-facade'));

        DB::table('users')->insert([
            'name' => 'Facade',
            'email' => 'facade@example.com',
            'password' => 'x',
        ]);
        $this->assertSame(1, DB::table('users')->count());

        $data = Validator::validate(
            ['email' => 'ok@example.com'],
            ['email' => 'required|email'],
        );
        $this->assertSame('ok@example.com', $data['email']);
    }
}
