<?php

declare(strict_types=1);

use SunnyPHP\Database\Connection;

return static function (Connection $db): void {
    $db->statement(
        'CREATE TABLE IF NOT EXISTS "users" (
            "id" INTEGER PRIMARY KEY AUTOINCREMENT,
            "name" TEXT NOT NULL,
            "email" TEXT NOT NULL UNIQUE,
            "password" TEXT NOT NULL,
            "created_at" TEXT,
            "updated_at" TEXT
        )',
    );
};
