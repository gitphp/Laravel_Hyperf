<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
return [
    'driver' => 'file',
    'cookie' => 'sunny_session',
    'lifetime' => 7200,
    'path' => dirname(__DIR__) . '/storage/framework/sessions',
];
