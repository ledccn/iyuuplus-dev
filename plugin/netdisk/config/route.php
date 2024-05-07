<?php

use Webman\Route;

$route = config('plugin.netdisk.io.route');

//分享路由
Route::any("/{$route}/{hash}", [\plugin\netdisk\app\controller\IndexController::class,'share']);
