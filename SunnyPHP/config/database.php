<?php

declare(strict_types=1);

/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */
return [
    'default' => 'mysql',
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/storage/database.sqlite',
        ],
        'mysql' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'sunnyphp',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
    ],
];
