<?php

namespace Iyuu\SiteManager\Contracts;

/**
 * 种子对象
 * - 一键推送下载任务到设备
 */
class Torrent
{
    /**
     * 站点名字
     * @var string
     */
    public readonly string $site;
    /**
     * 当前数据ID
     * @var int
     */
    public readonly int $id;
    /**
     * 站点ID
     * @var int
     */
    public readonly int $sid;
    /**
     * 站点内种子ID
     * @var int
     */
    public readonly int $torrent_id;
    /**
     * 站点内种子分组ID
     * @var int
     */
    public readonly int $group_id;

    /**
     * 种子的完整链接
     * @var string
     */
    public string $download = '';
    /**
     * 必须cookie才能下载种子
     * @var bool
     */
    public bool $downloadCookieRequired = false;

    /**
     * 构造函数
     * @param array $properties 原始的数据结构
     */
    public function __construct(public array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * 设置下载参数
     * @param string $download 下载链接
     * @param bool $downloadCookieRequired 下载种子是否需要cookie
     * @return void
     */
    public function setDownload(string $download, bool $downloadCookieRequired): void
    {
        $this->download = $download;
        $this->downloadCookieRequired = $downloadCookieRequired;
    }

    /**
     * 当对不可访问属性调用 isset() 或 empty() 时，__isset() 会被调用
     * @param int|string $name
     * @return bool
     */
    public function __isset(int|string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * 当访问不可访问属性时调用
     * @param int|string $name
     * @return array|string|null
     */
    public function __get(int|string $name)
    {
        return $this->get($name);
    }

    /**
     * 获取配置项参数
     * - 支持 . 分割符
     * @param int|string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    final public function get(int|string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->properties;
        }
        $keys = explode('.', $key);
        $value = $this->properties;
        foreach ($keys as $index) {
            if (!isset($value[$index])) {
                return $default;
            }
            $value = $value[$index];
        }
        return $value;
    }
}
