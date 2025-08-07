<?php

namespace Iyuu\BittorrentClient;

use Iyuu\BittorrentClient\Contracts\ConfigInterface;
use Iyuu\BittorrentClient\Driver\qBittorrent\Client;
use Ledc\Container\Manager;

/**
 * 下载器客户端
 */
class ClientDownloader extends Manager
{
    /**
     * 驱动的命名空间
     * @var string|null
     */
    protected ?string $namespace = __NAMESPACE__ . '\\Driver\\';
    /**
     * 始终创建新的驱动对象实例
     * @var bool
     */
    protected bool $alwaysNewInstance = true;

    /**
     * 构造函数
     * @param ConfigInterface $config 当前站点配置
     */
    public function __construct(public readonly ConfigInterface $config)
    {
    }

    /**
     * 默认驱动
     * @return string|null
     */
    public function getDefaultDriver(): ?string
    {
        return null;
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return string
     */
    protected function resolveType(string $name): string
    {
        [$uniqid, $brand] = $this->config->decode($name);
        return $brand;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name): array
    {
        return $this->config->get($name);
    }

    /**
     * 创建驱动
     * @param array $params
     * @return mixed
     */
    protected function createQBittorrentDriver(array ...$params): mixed
    {
        return static::app()->invokeClass(Client::class, $params);
    }

    /**
     * 创建驱动
     * @param array $params
     * @return mixed
     */
    protected function createTransmissionDriver(array ...$params): mixed
    {
        return static::app()->invokeClass(Driver\transmission\Client::class, $params);
    }

    /**
     * 选择下载器
     * @param string $name
     * @return Clients
     */
    public function select(string $name): Clients
    {
        return $this->driver($name);
    }
}
