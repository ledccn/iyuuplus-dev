<?php

return [
    [
        'title' => '管理中心',
        'key' => app\install\Installation::MENU_KEY,
        'icon' => 'layui-icon-component',
        'weight' => 300,
        'type' => 0,
        'children' => [
            [
                'title' => '下载器',
                'key' => app\admin\controller\ClientController::class,
                'icon' => 'layui-icon-circle-dot',
                'href' => '/admin/client/index',
                'type' => 1,
                'weight' => 0,
            ],
            [
                'title' => '站点',
                'key' => app\admin\controller\SiteController::class,
                'icon' => 'layui-icon-circle-dot',
                'href' => '/admin/site/index',
                'type' => 1,
                'weight' => 0,
            ],
            [
                'title' => '通知渠道',
                'icon' => 'layui-icon-circle-dot',
                'key' => app\admin\controller\NotifyController::class,
                'href' => '/admin/notify/index',
                'type' => 1,
                'weight' => 0,
            ],
            [
                'title' => '自动辅种',
                'icon' => 'layui-icon-circle-dot',
                'key' => app\admin\controller\ReseedController::class,
                'href' => '/admin/reseed/index',
                'type' => 1,
                'weight' => 0,
            ],
        ],
    ],
];
