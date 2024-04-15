<?php

return [
    [
        'title' => '计划任务',
        'icon' => 'layui-icon-date',
        'key' => 'crontab',
        'weight' => 280,
        'type' => 0,
        'children' => [
            [
                'title' => '任务管理',
                'icon' => 'layui-icon-date',
                'key' => plugin\cron\app\admin\controller\CrontabController::class,
                'href' => '/app/cron/admin/crontab/index',
                'weight' => 10,
                'type' => 1,
            ],
            [
                'title' => '任务日志',
                'icon' => 'layui-icon-log',
                'key' => plugin\cron\app\admin\controller\CrontabLogController::class,
                'href' => '/app/cron/admin/crontab-log/index',
                'weight' => 9,
                'type' => 1,
            ],
        ]
    ],
];
