<?php

namespace Iyuu\BittorrentClient;

use Iyuu\BittorrentClient\Contracts\ClientsInterface;
use Ledc\Curl\Curl;

/**
 * 客户端抽象类
 */
abstract class Clients implements ClientsInterface
{
    /**
     * 配置
     * @var Config
     */
    private Config $config;
    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * 构造函数
     * @param array $config
     */
    final public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->initCurl();
        $this->initialize();
    }

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * 初始化Curl
     * @return void
     */
    final protected function initCurl(): void
    {
        $this->curl = new Curl();
        $this->curl->setTimeout(60, 600);
        $this->curl->setSslVerify(false, false);
        $this->curl->setUserAgent(Curl::USER_AGENT);
        $this->curl->setHeader('Origin', $this->getConfig()->getHostname());
        $this->curl->setHeader('Referer', $this->getConfig()->getClientUrl());
    }

    /**
     * 获取配置
     * @return Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @return Curl
     */
    final public function getCurl(): Curl
    {
        return $this->curl;
    }
}
