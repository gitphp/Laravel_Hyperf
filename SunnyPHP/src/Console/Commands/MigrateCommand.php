<?php

declare(strict_types=1);

namespace SunnyPHP\Console\Commands;

use SunnyPHP\Application;
use SunnyPHP\Console\Command;
use SunnyPHP\Database\DatabaseManager;

final class MigrateCommand extends Command
{
    public function __construct(
        private Application $app,
        private DatabaseManager $db,
    ) {
    }

    #[\Override]
    public function name(): string
    {
        return 'migrate';
    }

    #[\Override]
    public function description(): string
    {
        return 'Run database migrations';
    }

    #[\Override]
    public function handle(array $args): int
    {
        $connection = $this->db->connection();
        $connection->statement(
            'CREATE TABLE IF NOT EXISTS "migrations" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "migration" TEXT NOT NULL UNIQUE, "ran_at" TEXT NOT NULL)',
        );

        $path = $this->app->path('database/migrations');
        $files = glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        $ran = 0;
        foreach ($files as $file) {
            $name = basename($file);
            $exists = $connection->selectOne('SELECT "id" FROM "migrations" WHERE "migration" = ?', [$name]);
            if ($exists !== null) {
                continue;
            }

            $migration = require $file;
            if (is_callable($migration)) {
                $migration($connection);
            }

            $connection->table('migrations')->insert([
                'migration' => $name,
                'ran_at' => date('c'),
            ]);
            if (!in_array('--quiet', $args, true)) {
                echo "Migrated: {$name}\n";
            }
            $ran++;
        }

        if ($ran === 0 && !in_array('--quiet', $args, true)) {
            echo "Nothing to migrate.\n";
        }

        return 0;
    }
}
