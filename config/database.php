<?php
/**
 * 数据库配置
 */

$database = dirname(__DIR__) . '/plugin/admin/config/database.php';
if (is_file($database)) {
    return include $database;
}

return [
    // 默认数据库
    'default' => 'mysql',
    // 各种数据库配置
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => runtime_path('/database.db'),
            'prefix' => '',
        ],
    ],
];
