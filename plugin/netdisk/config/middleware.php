<?php

use plugin\admin\api\Middleware;

return [
    '' => [
    ],
    'admin' => [
        Middleware::class,//webman-admin鉴权中间件
    ],
];