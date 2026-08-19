<?php

declare(strict_types=1);

use SunnyPHP\Application;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $file = __DIR__ . $path;
    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

$app = Application::create($root);
$app->boot();
$app->run();
