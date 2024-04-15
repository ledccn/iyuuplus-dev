<?php

/**
 * 进程配置
 */
$database = base_path('/plugin/admin/config/database.php');
if (!is_file($database) || !is_file(dirname(__DIR__, 3) . '/config/crontab.php')) {
    return [];
}

return [
    // 计划任务调度进程
    'scheduler' => [
        'handler' => plugin\cron\process\SchedulerProcess::class,
        'reloadable' => false,
        'constructor' => [
            // 通信密钥
            'secret' => config('crontab.secret', ''),
        ],
    ],
    // 计划任务工作者进程
    'worker' => [
        'handler' => plugin\cron\process\AsyncWorkerProcess::class,
        'listen' => config('crontab.async_listen', 'frame://127.0.0.1:8788'),
        'constructor' => [
            // 通信密钥
            'secret' => config('crontab.secret', ''),
        ],
        'count' => 2,
    ],
];
