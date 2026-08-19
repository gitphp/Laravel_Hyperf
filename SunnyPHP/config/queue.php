<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
return [
    'default' => 'sync',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'file' => [
            'driver' => 'file',
            'path' => dirname(__DIR__) . '/storage/framework/queue',
        ],
        'database' => [
            'driver' => 'database',
        ],
    ],
];
