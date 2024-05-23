<?php

namespace Iyuu\ReseedClient;

use Ledc\Curl\Curl;

/**
 * 辅种Curl基础类
 */
abstract class AbstractCurl
{
    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * 构造函数
     * @param string $token 辅种token
     */
    final public function __construct(public readonly string $token)
    {
        $this->curl = new Curl();
        $this->initCurl();
    }

    /**
     * 初始化Curl
     * @return void
     */
    protected function initCurl(): void
    {
        $this->curl->setTimeout(8, 8)->setSslVerify();
        if ($this->token) {
            $this->curl->setHeader('token', $this->token);
        }
    }

    /**
     * 重置当前类和curl
     * @return void
     */
    final public function reset(): void
    {
        $this->curl->reset();
        $this->initCurl();
    }

    /**
     * 获取Curl
     * @return Curl
     */
    final public function getCurl(): Curl
    {
        return $this->curl;
    }
}
