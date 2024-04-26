<?php
/**
 * Migration数据库迁移工具 Phinx
 * @link https://www.workerman.net/doc/webman/db/migration.html
 * @link https://github.com/cakephp/phinx
 * @link https://tsy12321.gitbooks.io/phinx-doc/content/
 */
require_once __DIR__ . '/bootstrap.php';

return [
    'paths' => [
        'migrations' => __DIR__ . '/db/migrations',
        'seeds' => __DIR__ . '/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'production_db',
            'user' => 'root',
            'pass' => 'production_db',
            'port' => '3306',
            'charset' => 'utf8',
        ],
        'development' => [
            'adapter' => 'mysql',
            'host' => getenv('DB_HOST'),
            'name' => getenv('DB_DATABASE'),
            'user' => getenv('DB_USERNAME'),
            'pass' => getenv('DB_PASSWORD'),
            'port' => getenv('DB_PORT'),
            'charset' => 'utf8mb4',
        ],
        'testing' => [
            'adapter' => 'mysql',
            'host' => 'localhost',
            'name' => 'testing_db',
            'user' => 'root',
            'pass' => 'testing_db',
            'port' => '3306',
            'charset' => 'utf8',
        ]
    ],
    'version_order' => 'creation'
];
