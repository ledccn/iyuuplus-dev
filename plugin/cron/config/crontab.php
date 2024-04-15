<?php

/**
 * 计划任务配置
 */
return [
    // 通信密钥
    'secret' => '{{secret}}',
    // 模型观察者目录
    'observer_directory' => runtime_path('/crontab/observer'),
    // 计划任务工作者进程 监听端口
    'async_listen' => 'frame://0.0.0.0:8788',
];
