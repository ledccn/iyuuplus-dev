<?php

return [
    [
        'title' => '在线网盘',
        'key' => 'netdisk',
        'icon' => 'pear-icon pear-icon-file-open',
        'weight' => 0,
        'type' => 0,
        'children' => [
            [
                'title' => '文档管理',
                'key' => 'plugin\\netdisk\\app\\admin\\controller\\IoSourceController',
                'href' => '/app/netdisk/admin/io-source/index',
                'type' => 1,
                'weight' => 800,
            ]
        ]
    ],
];
