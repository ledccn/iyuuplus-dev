<?php
/**
 * Session配置
 */

use Webman\Session\FileSessionHandler;

return [

    'type' => 'file', // or redis or redis_cluster
    'handler' => FileSessionHandler::class,
    'config' => [
        'file' => [
            'save_path' => runtime_path() . '/sessions',
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => '',
            'timeout' => 2,
            'database' => '',
            'prefix' => 'redis_session_',
        ],
        'redis_cluster' => [
            'host' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7001'],
            'timeout' => 2,
            'auth' => '',
            'prefix' => 'redis_session_',
        ]
    ],
    'session_name' => 'PHPSID',
    'auto_update_timestamp' => false,
    // session过期时间
    'lifetime' => 30 * 24 * 60 * 60,
    // 存储session_id的cookie过期时间
    'cookie_lifetime' => 365 * 24 * 60 * 60,
    // 存储session_id的cookie路径
    'cookie_path' => '/',
    // 存储session_id的cookie域名
    'domain' => '',
    // 是否开启httpOnly，默认开启
    'http_only' => true,
    // 仅在https下开启session，默认关闭
    'secure' => false,
    // 用于防止CSRF攻击和用户追踪，可选值strict/lax/none
    'same_site' => '',
    // 回收session的几率
    'gc_probability' => [1, 1000],
];
