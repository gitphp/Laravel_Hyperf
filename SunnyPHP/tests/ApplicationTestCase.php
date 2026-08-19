<?php

declare(strict_types=1);

namespace SunnyPHP\Tests;

use PHPUnit\Framework\TestCase;
use SunnyPHP\Application;
use SunnyPHP\Config\Repository;
use SunnyPHP\Console\Commands\MigrateCommand;
use SunnyPHP\Database\DatabaseManager;

abstract class ApplicationTestCase extends TestCase
{
    protected function bootApp(): Application
    {
        $app = Application::create(dirname(__DIR__));
        $app->boot();
        $config = $app->container->get(Repository::class);
        $config->set('database.connections.sqlite.database', ':memory:');
        $config->set('cache.default', 'array');
        $config->set('session.driver', 'array');
        $config->set('queue.default', 'sync');

        return $app;
    }

    protected function migrate(Application $app): void
    {
        $app->container->make(MigrateCommand::class)->handle(['--quiet']);
    }

    protected function db(Application $app): DatabaseManager
    {
        return $app->container->make(DatabaseManager::class);
    }
}
