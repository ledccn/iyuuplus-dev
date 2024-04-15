<?php

use Webman\Push\Server;

return [
    'server' => [
        'handler'     => Server::class,
        'listen'      => config('plugin.webman.push.app.websocket'),
        'count'       => 1, // 必须是1
        'reloadable'  => false, // 执行reload不重启
        'constructor' => [
            'api_listen' => config('plugin.webman.push.app.api'),
            'app_info'   => [
                config('plugin.webman.push.app.app_key') => [
                    'channel_hook' => config('plugin.webman.push.app.channel_hook'),
                    'app_secret'   => config('plugin.webman.push.app.app_secret'),
                ],
            ]
        ]
    ]
];