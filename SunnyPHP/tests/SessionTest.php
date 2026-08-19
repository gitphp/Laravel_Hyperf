<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;
use SunnyPHP\Session\SessionManager;
use SunnyPHP\Session\StartSession;

final class SessionTest extends ApplicationTestCase
{
    public function testGetSetAndFlash(): void
    {
        $app = $this->bootApp();
        $session = $app->container->make(SessionManager::class);
        $session->start();
        $session->set('user', 'ada');
        $this->assertTrue($session->has('user'));
        $this->assertSame('ada', $session->get('user'));

        $session->flash('notice', 'saved');
        $this->assertSame('saved', $session->get('notice'));
        $id = $session->id();
        $session->save();

        $session->start($id);
        $this->assertSame('saved', $session->get('notice'));
        $this->assertSame('ada', $session->get('user'));
        $session->save();

        $session->start($id);
        $this->assertNull($session->get('notice'));
        $this->assertSame('ada', $session->get('user'));
    }

    public function testStartSessionMiddlewareSetsCookie(): void
    {
        $app = $this->bootApp();
        $middleware = $app->container->make(StartSession::class);
        $response = $middleware->process(
            Request::create('GET', '/'),
            new class implements \SunnyPHP\Contracts\RequestHandlerInterface {
                public function handle(Request $request): Response
                {
                    return Response::text('ok');
                }
            },
        );

        $this->assertSame('ok', $response->body());
        $this->assertNotEmpty($response->cookies());
        $this->assertSame('sunny_session', $response->cookies()[0]['name']);
    }
}
