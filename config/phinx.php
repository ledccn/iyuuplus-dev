<?php
/**
 * Migration数据库迁移工具 Phinx
 * @link https://www.workerman.net/doc/webman/db/migration.html
 * @link https://github.com/cakephp/phinx
 * @link https://tsy12321.gitbooks.io/phinx-doc/content/
 */
return [
    "paths" => [
        "migrations" => "database/migrations",
        "seeds"      => "database/seeds"
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_database"        => "dev",
        "default_environment"     => "dev",
        "dev" => [
            "adapter" => "DB_CONNECTION",
            "host"    => getenv('DB_HOST'),
            "name"    => getenv('DB_DATABASE'),
            "user"    => getenv('DB_USERNAME'),
            "pass"    => getenv('DB_PASSWORD'),
            "port"    => getenv('DB_PORT'),
            "charset" => "utf8mb4"
        ],
    ]
];
