<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
use App\Http\Controllers\HomeController;
use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;
use SunnyPHP\Routing\Router;

/** @var Router $router */

$router->get('/', [HomeController::class, 'index']);

$router->get('/hello/{name}', function (Request $request): Response {
    return Response::json([
        'message' => 'Hello, ' . $request->attribute('name'),
    ]);
});

$router->get('/demo/aop', function (): Response {
    $result = \SunnyPHP\Facade\App::make(\App\Services\DemoService::class)->ping();

    return Response::json(['result' => $result]);
});
