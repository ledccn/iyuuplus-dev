<?php

namespace Iyuu\SiteManager;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * 站点管理器专用缓存
 * Strings methods
 * @method static mixed get($key, $default = null)
 * @method static bool set($key, $value, $ttl = null)
 * @method static bool delete($key)
 * @method static bool clear()
 * @method static iterable getMultiple($keys, $default = null)
 * @method static bool setMultiple($values, $ttl = null)
 * @method static bool deleteMultiple($keys)
 * @method static bool has($key)
 */
final class SiteManagerCache
{
    /**
     * @var Psr16Cache|null
     */
    private static ?Psr16Cache $cache = null;

    /**
     * 获取缓存实例
     * @return Psr16Cache|null
     */
    public static function getCache(): ?Psr16Cache
    {
        return self::$cache;
    }

    /**
     * 设置缓存实例
     * @param Psr16Cache $cache
     */
    public static function setCache(Psr16Cache $cache): void
    {
        self::$cache = $cache;
    }

    /**
     * 站点管理器专用缓存
     * @return Psr16Cache
     */
    public static function getInstance(): Psr16Cache
    {
        if (!self::$cache) {
            $adapter = new FilesystemAdapter('', 0, runtime_path('cache_site_manager'));
            self::$cache = new Psr16Cache($adapter);
        }
        return self::$cache;
    }

    /**
     * 静态方法代理
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->{$name}(... $arguments);
    }
}
