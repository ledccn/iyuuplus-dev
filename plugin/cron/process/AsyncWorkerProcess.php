<?php

namespace plugin\cron\process;

use plugin\cron\api\Install;
use support\Container;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * 计划任务工作进程
 */
class AsyncWorkerProcess
{
    /**
     * @var Worker
     */
    protected static Worker $worker;

    /**
     * 构造函数
     * @param string $secret 通信密钥
     */
    public function __construct(protected readonly string $secret = '')
    {
    }

    /**
     * 子进程启动时的回调函数
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        static::$worker = $worker;
    }

    /**
     * 当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数
     * @param TcpConnection $connection 连接对象
     * @param string $data 客户端连接上发来的数据
     * @return void
     */
    public function onMessage(TcpConnection $connection, string $data): void
    {
        $code = 0;
        $msg = '';
        $res = null;
        if (!Install::isInstalled()) {
            $connection->send(json_encode([
                'code' => '500',
                'msg' => '计划任务未安装',
                'data' => $res,
                'worker_id' => static::$worker->id,
            ], JSON_UNESCAPED_UNICODE));
            return;
        }

        $payload = json_decode($data, true);
        $class = $payload['class'] ?? '';
        $method = $payload['method'] ?? '';
        $secret = $payload['secret'] ?? '';
        $parameter = $payload['parameter'] ?? [];
        $worker_id = static::$worker->id;
        if (class_exists($class) && method_exists($class, $method)) {
            try {
                if (empty($this->secret) || $this->secret === $secret) {
                    $instance = Container::get($class);
                    $return = call_user_func_array([$instance, $method], array_values($parameter));
                    $res = $return ?? null;
                } else {
                    $code = 403;
                    $msg = '通信密钥错误';
                }
            } catch (Throwable $throwable) {
                $code = 500;
                $msg = $throwable->getMessage();
            }
        } else {
            $code = 404;
            $msg = '类不存在或方法不可调用：' . json_encode([$class, $method]);
        }

        $connection->send(json_encode([
            'code' => $code,
            'msg' => $msg,
            'data' => $res,
            'worker_id' => $worker_id,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 子进程退出时的回调函数
     * @return void
     */
    public function onWorkerStop()
    {
    }

    /**
     * 设置Worker收到reload信号后执行的回调
     * @return void
     */
    public function onWorkerReload()
    {
    }
}
