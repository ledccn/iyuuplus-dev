<?php

namespace app\middleware;

use app\install\Installation;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 首次使用时安装中间件
 */
class Install implements MiddlewareInterface
{
    /**
     * 契约方法
     * @param Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(Request $request, callable $handler): Response
    {
        match ($request->path()) {
            '/app/admin/config/get' => Installation::install(),
            default => false
        };
        return $handler($request);
    }
}
