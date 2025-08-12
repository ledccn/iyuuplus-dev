<?php

/**
 * 进程配置
 */

return [
    // 计划任务调度进程
    'scheduler' => [
        'workerClass' => plugin\cron\app\Worker::class,
        'handler' => plugin\cron\process\SchedulerProcess::class,
        //'reloadable' => false,
        'constructor' => [
            // 通信密钥
            'secret' => config('crontab.secret', ''),
        ],
    ],
    // 计划任务工作者进程
    'worker' => [
        'workerClass' => plugin\cron\app\Worker::class,
        'handler' => plugin\cron\process\AsyncWorkerProcess::class,
        'listen' => config('crontab.async_listen', 'frame://127.0.0.1:8788'),
        'constructor' => [
            // 通信密钥
            'secret' => config('crontab.secret', ''),
        ],
        'count' => 2,
    ],
];
