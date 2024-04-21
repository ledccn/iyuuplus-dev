<?php

namespace app\exception;

use support\exception\BusinessException;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 本地admin应用的异常处理类
 */
class AdminHandler extends ExceptionHandler
{
    /**
     * @var string[]
     */
    public $dontReport = [
        BusinessException::class,
        ValidateException::class,
    ];

    /**
     * @param Request $request
     * @param Throwable $exception
     * @return Response
     */
    public function render(Request $request, Throwable $exception): Response
    {
        $code = $exception->getCode();
        $msg = match (true) {
            $this->debug, $this->shouldntReport($exception) => $exception->getMessage(),
            default => 'Server internal error',
        };
        if ($request->expectsJson()) {
            $json = ['code' => $code ?: 500, 'msg' => $msg, 'type' => 'failed'];
            $this->debug && $json['traces'] = (string)$exception;
            return new Response(200, ['Content-Type' => 'application/json'],
                \json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        $error = $this->debug ? \nl2br((string)$exception) : $msg;
        return new Response(500, [], $error);
    }
}
