<?php

namespace process;

use Exception;
use Iyuu\PacificSdk\Api;
use Iyuu\PacificSdk\Contracts\ResponsePusher;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

/**
 * 推送服务
 */
class MovieProcess
{
    /**
     * @var Api
     */
    protected Api $api;
    /**
     * 服务器下发的长连接配置
     * @var ResponsePusher
     */
    protected ResponsePusher $response;
    /**
     * 异步WebSocket连接
     * @var AsyncTcpConnection|null
     */
    protected ?AsyncTcpConnection $connection = null;

    /**
     * 构造函数
     * @param string $token
     */
    public function __construct(public readonly string $token)
    {
    }

    /**
     * 子进程启动时执行
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        try {
            $api = new Api($this->token);
            $responsePusher = $api->getPusher();
            $this->connection = new AsyncTcpConnection($responsePusher->url, $this->getContextOption());
        } catch (Throwable $throwable) {
            return;
        }
    }

    /**
     * @return AsyncTcpConnection
     * @throws Exception
     */
    private function connect(): AsyncTcpConnection
    {
        $connection = new AsyncTcpConnection($this->response->url . '/app/' . $this->response->app_key, $this->getContextOption());
        $connection->onConnect = function (AsyncTcpConnection $connection) {
        };
        $connection->onMessage = function (AsyncTcpConnection $connection, mixed $data) {
            $result = json_decode($data, true);
            if ($this->response->uid) {

            } else {

            }
        };
        $connection->onClose = function (AsyncTcpConnection $connection) {
        };
        $connection->onError = function (AsyncTcpConnection $connection, $code, $msg) {
        };

        return $connection;
    }

    /**
     * 设置访问对方主机的本地ip及端口(每个socket连接都会占用一个本地端口)
     * @return array
     */
    private function getContextOption(): array
    {
        return [
            'socket' => [
                // ip必须是本机网卡ip，并且能访问对方主机，否则无效
                'bindto' => '114.215.84.87:2333',
            ],
            // ssl选项，参考https://php.net/manual/zh/context.ssl.php
            'ssl' => [
                // 本地证书路径。 必须是 PEM 格式，并且包含本地的证书及私钥。
                'local_cert' => '/your/path/to/pemfile',
                // local_cert 文件的密码。
                'passphrase' => 'your_pem_passphrase',
                // 是否允许自签名证书。
                'allow_self_signed' => true,
                // 是否需要验证 SSL 证书。
                'verify_peer' => false
            ]
        ];
    }
}
