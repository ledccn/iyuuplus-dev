<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * Token转换Session
 * - 从请求头获取token值，设置session_id
 */
class SessionId implements MiddlewareInterface
{
    /**
     * @param \support\Request|Request $request
     * @param callable $handler
     * @return Response
     */
    public function process(\support\Request|Request $request, callable $handler): Response
    {
        $token = $request->header('token');
        if ($token && ctype_alnum($token) && strlen($token) <= 40) {
            $request->sessionId($token);
        }
        return $handler($request);
    }
}
