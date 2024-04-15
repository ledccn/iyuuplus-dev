<?php

namespace Iyuu\PacificSdk;

use Ledc\Curl\Curl;
use RuntimeException;

/**
 * API接口
 */
abstract class Pacific
{
    /**
     * 域名和协议
     * @var string
     */
    protected readonly string $host;
    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * 构造函数
     * @param string $serverAddress 服务器地址
     * @param string $token 请求token
     */
    final public function __construct(public readonly string $serverAddress, public readonly string $token)
    {
        $scheme = parse_url($serverAddress, PHP_URL_SCHEME);
        $host = parse_url($serverAddress, PHP_URL_HOST);
        $this->host = $scheme . '://' . $host;
        $this->initCurl();
        $this->initialize();
    }

    /**
     * 初始化Curl
     * @return void
     */
    final protected function initCurl(): void
    {
        $this->curl = new Curl();
        $this->curl->setTimeout(10, 30)
            ->setSslVerify(false, false)
            ->setHeader('token', $this->token);
    }

    /**
     * 请求失败，抛出异常
     * @param Curl $curl
     * @return void
     */
    protected function throwException(Curl $curl): void
    {
        $errmsg = $curl->error_message ?: 'error_message为空';

        throw new RuntimeException('请求失败：' . $errmsg);
    }

    /**
     * 解析响应DATA
     * @param Curl $curl
     * @return array
     */
    protected function parseResponseData(Curl $curl): array
    {
        $response = json_decode($curl->response, true);
        $code = $response['code'] ?? false;
        $data = $response['data'] ?? [];
        $msg = $response['msg'] ?? '错误描述消息为空';
        if (1 === $code) {
            return $data;
        }

        throw new RuntimeException('解析DATA失败：' . $msg);
    }

    /**
     * 子类初始化
     * @return void
     */
    abstract protected function initialize(): void;
}
