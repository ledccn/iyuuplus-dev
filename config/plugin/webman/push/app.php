<?php

return [
    'enable'       => true,
    'websocket'    => 'websocket://' . (getenv('IYUU_LISTEN_IPV6') ? '[::]' : '0.0.0.0') . ':3131',
    'api'          => 'http://0.0.0.0:3232',
    'app_key'      => 'd9422b72cffad23098ad301eea0f8419',
    'app_secret'   => 'b3ac18ae098b8e87b179299f8cc860a2',
    'channel_hook' => 'http://127.0.0.1:8787/plugin/webman/push/hook',
    'auth'         => '/plugin/webman/push/auth'
];