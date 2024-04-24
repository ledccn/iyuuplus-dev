<?php
/**
 * Http服务配置
 */

//读取环境变量内服务监听端口
$listenPort = getenv('SERVER_LISTEN_PORT');
if (false === $listenPort || !is_numeric($listenPort)) {
    $listenPort = '8787';
}

return [
    'listen' => (getenv('IYUU_LISTEN_IPV6') ? 'http://[::]:' : 'http://0.0.0.0:') . $listenPort,
    'transport' => 'tcp',
    'context' => [],
    'name' => 'webman',
    'count' => cpu_count() * 2,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => runtime_path() . '/webman.pid',
    'status_file' => runtime_path() . '/webman.status',
    'stdout_file' => runtime_path() . '/logs/stdout.log',
    'log_file' => runtime_path() . '/logs/workerman.log',
    'max_package_size' => 10 * 1024 * 1024
];
