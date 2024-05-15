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
     * 种子列表key
     */
    public const string TORRENT_LIST = 'lists';
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
        $this->curl = $this->initCurl();
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
     * @return Curl
     */
    final protected function initCurl(): Curl
    {
        $curl = new Curl();
        $curl->setTimeout(60, 600);
        $curl->setSslVerify(false, false);
        $curl->setUserAgent(Curl::USER_AGENT);
        $curl->setHeader('Origin', $this->getConfig()->getHostname());
        $curl->setHeader('Referer', $this->getConfig()->getClientUrl());

        return $curl;
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
