<?php

use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\RedisStore;

/**
 * 业务锁配置
 */
return [
    // file|redis，建议使用redis；file不支持 ttl
    'storage' => 'file',
    'storage_configs' => [
        'file' => [
            'class' => FlockStore::class,
            'construct' => [
                'lockPath' => runtime_path() . '/lock',
            ],
        ],
        'redis' => [
            'class' => RedisStore::class,
            'construct' => function () {
                return [
                    'redis' => \support\Redis::connection('default')->client(),
                ];
            },
        ],
    ],
    'default_config' => [
        // 默认锁超时时间
        'ttl' => 300,
        // 是否自动释放，建议设置为 true
        'auto_release' => true,
        // 锁前缀
        'prefix' => 'lock_',
    ],
];
