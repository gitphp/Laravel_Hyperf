<?php

declare(strict_types=1);

use SunnyPHP\Database\Connection;

return static function (Connection $db): void {
    $db->statement(
        'CREATE TABLE IF NOT EXISTS "jobs" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT,
            "queue" TEXT NOT NULL DEFAULT \'default\',
            "payload" TEXT NOT NULL,
            "attempts" INTEGER NOT NULL DEFAULT 0,
            "available_at" INTEGER NOT NULL,
            "created_at" INTEGER NOT NULL
        )',
    );
};
