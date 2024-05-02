<?php

namespace process;

use app\admin\services\client\ClientServices;
use app\admin\services\download\DownloaderServices;
use Exception;
use InvalidArgumentException;
use Iyuu\PacificSdk\Api;
use Iyuu\PacificSdk\Contracts\ResponsePusher;
use Ledc\Container\App;
use support\Log;
use Throwable;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;
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
     * ping定时器ID
     * @var int
     */
    private int $pingTimerId = 0;
    /**
     * 没有收到pong的次数
     * @var int
     */
    private int $notSendPongCount = 0;
    /**
     * 最后收到服务器消息的时间戳
     * @var int
     */
    protected int $lastMessageTime = 0;

    /**
     * 构造函数
     * @param string $token
     * @param bool $debug 调试
     */
    public function __construct(public readonly string $token, public readonly bool $debug = true)
    {
    }

    /**
     * 子进程启动时执行
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        if (empty($this->token)) {
            return;
        }

        try {
            $this->api = $api = new Api($this->token);
            $this->response = $api->getPusher();
            $this->connection = $this->connect();
        } catch (Throwable $throwable) {
            echo __METHOD__ . ' 异常' . $throwable->getMessage() . PHP_EOL;
        }
    }

    /**
     * @return AsyncTcpConnection
     * @throws Exception
     */
    private function connect(): AsyncTcpConnection
    {
        if (str_starts_with($this->response->url, 'wss://')) {
            $url = 'ws://' . substr($this->response->url, strlen('wss://')) . ':443';
            // 设置以ssl加密方式访问，使之成为wss
            $transport = 'ssl';
        } else {
            $url = $this->response->url . ':80';
            $transport = 'tcp';
        }

        $connection = new AsyncTcpConnection($url . '/app/' . $this->response->app_key);
        $connection->transport = $transport;
        $connection->onConnect = function (AsyncTcpConnection $connection) {
            $this->notSendPongCount = 0;
        };

        // websocket握手成功后
        $connection->onWebSocketConnect = function (AsyncTcpConnection $connection) {
            $this->notSendPongCount = 0;
            if ($this->pingTimerId) {
                Timer::del($this->pingTimerId);
            }

            $this->pingTimerId = Timer::add(30, function () {
                $this->response('pusher:ping');
                if (1 < $this->notSendPongCount) {
                    $this->connection->close();
                }
                $this->notSendPongCount++;
            });
        };

        $connection->onMessage = function (AsyncTcpConnection $connection, mixed $data) {
            $this->lastMessageTime = time();
            $this->notSendPongCount = 0;
            $result = json_decode($data, true);
            $this->safeEcho($result);
            $event = $result['event'] ?? '';
            switch ($event) {
                // 初始化事件
                case 'pusher:connection_established':
                    $d = json_decode($result['data']);
                    if ($this->response->uid) {
                        $response = $this->api->pushAuth($this->response->uid, $d->socket_id);
                        //echo $response . PHP_EOL;
                        $response_array = json_decode($response, true);
                        $response_array['channel'] = $this->api->getChannelName($this->response->uid);
                        $this->response('pusher:subscribe', $response_array);
                    }
                    break;
                // 关注成功事件
                case 'pusher_internal:subscription_succeeded':
                    $channel = $result['channel'];
                    if ($this->api->getChannelName($this->response->uid) === $channel) {
                        echo '设备上线成功！' . PHP_EOL;
                    }
                    break;
                // pong事件
                case 'pusher:pong':
                    $this->notSendPongCount = 0;
                    break;
                // 下载种子事件
                case 'client_download':
                    $this->handleClientDownload(json_decode($result['data'], true));
                    break;
                default:
                    break;
            }
        };
        $connection->onClose = function (AsyncTcpConnection $connection) {
            $this->connection->reconnect(3);
        };
        $connection->onError = function (AsyncTcpConnection $connection, $code, $msg) {
            echo "Error code:$code msg:$msg" . PHP_EOL;
        };
        $connection->connect();

        return $connection;
    }

    /**
     * 处理下载种子的事件
     * @param array $payload
     * @return void
     */
    private function handleClientDownload(array $payload): void
    {
        try {
            if (empty($payload)) {
                throw new InvalidArgumentException('参数错误');
            }

            /** @var DownloaderServices $downloadServices */
            $downloadServices = App::pull(DownloaderServices::class);

            $response = $downloadServices->download($payload['torrent']);
            $model = ClientServices::getDefaultClient();
            $result = ClientServices::sendClientDownloader($response, $model);
            Log::info(__METHOD__ . ' | 下载成功' . PHP_EOL . json_encode($payload, JSON_UNESCAPED_UNICODE));
            $this->api->update($payload['id'], 3, is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE));
        } catch (Throwable $throwable) {
            Log::error(__METHOD__ . ' | ' . $throwable->getMessage() . PHP_EOL . json_encode($payload, JSON_UNESCAPED_UNICODE));
            $this->api->update($payload['id'], 2, $throwable->getMessage());
        }
    }

    /**
     * @param mixed $data
     * @return void
     */
    private function safeEcho(mixed $data): void
    {
        if ($this->debug) {
            print_r($data);
        }
    }

    /**
     * 发送响应
     * @param string $event
     * @param array $data
     * @return void
     */
    private function response(string $event, array $data = []): void
    {
        $this->connection->send(json_encode(['event' => $event, 'data' => $data]));
    }
}
