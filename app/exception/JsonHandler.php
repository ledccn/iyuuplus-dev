<?php

namespace app\exception;

use support\exception\BusinessException;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;

/**
 * 异常信息以Json返回
 */
class JsonHandler extends ExceptionHandler
{
    /**
     * @var string[]
     */
    public $dontReport = [
        BusinessException::class,
        ValidateException::class,
    ];

    /**
     * 渲染返回
     * @param Request $request
     * @param Throwable $exception
     * @return Response
     */
    public function render(Request $request, Throwable $exception): Response
    {
        $header = [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache', //禁止缓存
            'Pragma' => 'no-cache', //禁止缓存
        ];

        $rs = [
            'code' => $exception->getCode() ?: 400,
            'msg' => match (true) {
                $this->debug, $this->shouldntReport($exception) => $exception->getMessage(),
                default => 'Server internal error',
            },
        ];
        if ($this->debug) {
            $rs['traces'] = (string)$exception;
        }

        return new Response(200, $header, json_encode($rs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}