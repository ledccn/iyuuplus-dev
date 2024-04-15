<?php

namespace support;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * 自定义文件缓存类
 *
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
class Cache
{
    /**
     * @var ?Psr16Cache
     */
    public static ?Psr16Cache $instance = null;

    /**
     * @return Psr16Cache
     */
    public static function instance(): Psr16Cache
    {
        if (!static::$instance) {
            $adapter = new FilesystemAdapter('file_cache', 3600, runtime_path());
            self::$instance = new Psr16Cache($adapter);
        }
        return static::$instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return static::instance()->{$name}(... $arguments);
    }
}
