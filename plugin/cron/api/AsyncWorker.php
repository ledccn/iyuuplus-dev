<?php

namespace plugin\cron\api;

use Closure;
use Error;
use Exception;
use InvalidArgumentException;
use plugin\cron\app\model\Crontab;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;

/**
 * 异步工作进程
 */
class AsyncWorker
{
    /**
     * 投递到异步进程执行
     * @param string $class 完整的类名
     * @param string $method 类的方法名称
     * @param array $parameter 传入的参数
     * @param Closure|null $closure 用来接收返回值的匿名函数，原型为$callback(?string $result, Exception|null $e)
     * @return void
     * @throws Error|Exception|Throwable
     */
    public static function send(string $class, string $method = 'execute', array $parameter = [], Closure $closure = null): void
    {
        try {
            // 通讯协议frame
            $taskConnection = new AsyncTcpConnection(str_replace('0.0.0.0', '127.0.0.1', config('crontab.async_listen')));
            $taskConnection->onMessage = function (AsyncTcpConnection $asyncTcpConnection, $taskResult) use ($closure) {
                if ($closure instanceof Closure) {
                    call_user_func($closure, $taskResult, null);
                }
                $asyncTcpConnection->close();
            };
            $taskConnection->send(json_encode([
                'class' => $class,
                'method' => $method,
                'secret' => config('crontab.secret', ''),
                'parameter' => $parameter
            ]));
            $taskConnection->connect();
        } catch (Error|Exception|Throwable $e) {
            if ($closure instanceof Closure) {
                call_user_func($closure, null, new Exception($e->getMessage(), $e->getCode()));
            } else {
                throw $e;
            }
        }
    }

    /**
     * 【立刻运行】执行类方法
     * @param Crontab $model
     * @param Closure|null $closure 用来接收返回值的匿名函数，原型为$callback(?string $result, Exception|null $e)
     * @return void
     * @throws Throwable
     */
    public static function runClassMethod(Crontab $model, Closure $closure = null): void
    {
        $target = $model->target;
        $parameter = $model->parameter ? json_decode($model->parameter, true) : [];
        if (str_contains($target, '@')) {
            [$class, $method] = explode('@', $target);
        } else {
            [$class, $method] = [$target, 'execute'];
        }
        if (class_exists($class) && method_exists($class, $method)) {
            static::send($class, $method, $parameter, $closure);
        } else {
            throw new InvalidArgumentException("类或者方法不存在：{$class}@{$method}");
        }
    }
}
