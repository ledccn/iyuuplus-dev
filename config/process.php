<?php
/**
 * 自定义进程配置
 */

global $argv;

//读取环境变量内服务监听端口
$listenPort = getenv('SERVER_LISTEN_PORT');
if (false === $listenPort || !is_numeric($listenPort)) {
    $listenPort = '8787';
}

return [
    // File update detection and automatic reload
    'monitor' => [
        'handler' => process\Monitor::class,
        'reloadable' => false,
        'constructor' => [
            // Monitor these directories
            'monitorDir' => array_merge([
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/.env',
            ], glob(base_path() . '/plugin/*/app'), glob(base_path() . '/plugin/*/config'), glob(base_path() . '/plugin/*/api')),
            // Files with these suffixes will be monitored
            'monitorExtensions' => [
                'php', 'html', 'htm', 'env'
            ],
            'options' => [
                'enable_file_monitor' => env('APP_DEBUG', true) && !in_array('-d', $argv) && DIRECTORY_SEPARATOR === '/',
                'enable_memory_monitor' => DIRECTORY_SEPARATOR === '/',
            ]
        ]
    ],
    // IYUU辅助进程：辅种、RSS等
    'reseed' => [
        'handler' => process\ReseedProcess::class,
        'constructor' => [],
    ],
    // 视听云
    'cloud' => [
        'handler' => process\MovieProcess::class,
        'constructor' => [
            'token' => getenv('CLOUD_ACCESS_TOKEN') ?: '',
            'debug' => (bool)getenv('CLOUD_DEBUG'),
        ],
    ],
];
