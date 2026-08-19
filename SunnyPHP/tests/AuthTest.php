<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use App\Model\User;
use SunnyPHP\Auth\AuthManager;

final class AuthTest extends ApplicationTestCase
{
    public function testAttemptLoginAndLogout(): void
    {
        $app = $this->bootApp();
        $this->migrate($app);

        User::create([
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => password_hash('secret', PASSWORD_DEFAULT),
        ]);

        $auth = $app->container->make(AuthManager::class);
        $this->assertTrue($auth->guest());
        $this->assertFalse($auth->attempt(['email' => 'ada@example.com', 'password' => 'wrong']));
        $this->assertTrue($auth->attempt(['email' => 'ada@example.com', 'password' => 'secret']));
        $this->assertTrue($auth->check());
        $user = $auth->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Ada', $user->name);
        $this->assertNotNull($auth->id());

        $auth->logout();
        $this->assertTrue($auth->guest());
        $this->assertNull($auth->user());
    }
}
