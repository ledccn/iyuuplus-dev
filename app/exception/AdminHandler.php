<?php

namespace app\exception;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use support\exception\BusinessException;
use support\exception\NotFoundException;
use think\exception\ClassNotFoundException;
use think\exception\FuncNotFoundException;
use think\exception\ValidateException;
use Throwable;
use Webman\Exception\ExceptionHandler;
use Webman\Exception\FileException;
use Webman\Http\Request;
use Webman\Http\Response;
use function json_encode;
use function nl2br;

/**
 * 本地admin应用的异常处理类
 */
class AdminHandler extends ExceptionHandler
{
    /**
     * @var string[]
     */
    public $dontReport = [
        // 异常：业务
        BusinessException::class,
        // 异常：验证器
        ValidateException::class,
    ];

    /**
     * 异常白名单
     * - 在白名单内，返回详细的异常描述
     * @var array
     */
    public const array whiteListException = [
        // 异常：业务
        BusinessException::class,
        // 异常：类或函数不存在
        NotFoundException::class,
        // 异常：类不存在
        ClassNotFoundException::class,
        // 异常：函数不存在
        FuncNotFoundException::class,
        // 异常：验证器
        ValidateException::class,
        // 异常：文件
        FileException::class,
        // 异常：无效参数
        InvalidArgumentException::class,
        // 异常：运行时
        RuntimeException::class,
        // 异常：逻辑错误
        LogicException::class,
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
            $this->debug, $this->canWhiteList($exception) => $exception->getMessage(),
            default => 'Server internal error',
        };
        if ($request->expectsJson()) {
            $json = ['code' => $code ?: 500, 'msg' => $msg, 'type' => 'failed'];
            if ($this->debug) {
                $json['traces'] = (string)$exception;
            }
            return new Response(200, ['Content-Type' => 'application/json'],
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        $error = $this->debug ? nl2br((string)$exception) : $msg;
        return new Response(500, [], $error);
    }

    /**
     * @param Throwable $exception
     * @return bool
     */
    private function canWhiteList(Throwable $exception): bool
    {
        foreach (static::whiteListException as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }
        return false;
    }
}
