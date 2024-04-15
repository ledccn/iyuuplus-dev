<?php

namespace Iyuu\BittorrentClient;

use InvalidArgumentException;
use Iyuu\BittorrentClient\Contracts\ConfigInterface;
use Ledc\Container\App;
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
     * 构造函数
     * @param App $app App
     * @param ConfigInterface $config 当前站点配置
     */
    public function __construct(App $app, public readonly ConfigInterface $config)
    {
        parent::__construct($app);
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
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass(string $type): string
    {
        $class = str_contains($type, '\\') ? $type : $this->namespace . $type . '\\Client';
        if (class_exists($class)) {
            return $class;
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
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

    /**
     * 清理所有驱动实例
     * @return void
     */
    public function clearDriver(): void
    {
        $this->drivers = [];
    }
}
