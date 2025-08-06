<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'boot.php';

/**
 * 应用配置
 */

use support\Request;

return [
    'debug' => env('APP_DEBUG', true),
    'error_reporting' => E_ALL,
    'default_timezone' => 'Asia/Shanghai',
    'request_class' => Request::class,
    'public_path' => base_path() . DIRECTORY_SEPARATOR . 'public',
    'runtime_path' => base_path(false) . DIRECTORY_SEPARATOR . 'runtime',
    'controller_suffix' => 'Controller',
    'controller_reuse' => false,
];
