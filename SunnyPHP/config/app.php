<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
return [
    'name' => 'SunnyPHP',
    'debug' => true,
    'timezone' => 'UTC',
    'middleware' => [
        App\Http\Middleware\RequestId::class,
        SunnyPHP\Session\StartSession::class,
    ],
];
