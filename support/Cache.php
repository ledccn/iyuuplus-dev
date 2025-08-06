<?php

namespace support;

use InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Class Cache
 * @package support\bootstrap
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
     * @var Psr16Cache[]
     */
    public static array $instances = [];

    /**
     * 缓存实例
     * @param string|null $name
     * @return Psr16Cache
     */
    public static function store(?string $name = null): Psr16Cache
    {
        $name = $name ?: config('cache.default', 'file');
        $stores = config('cache.stores', []);
        if (!isset($stores[$name])) {
            throw new InvalidArgumentException("cache.store.$name is not defined. Please check config/cache.php");
        }
        if (!isset(static::$instances[$name])) {
            $driver = $stores[$name]['driver'];
            switch ($driver) {
                case 'redis':
                    $client = Redis::connection($stores[$name]['connection'])->client();
                    $adapter = new RedisAdapter($client);
                    break;
                case 'file':
                    $adapter = new FilesystemAdapter('', 0, $stores[$name]['path']);
                    break;
                case 'array':
                    $adapter = new ArrayAdapter(0, $stores[$name]['serialize'] ?? false, 0, 0);
                    break;
                default:
                    throw new InvalidArgumentException("cache.store.$name.driver=$driver is not supported.");
            }
            static::$instances[$name] = new Psr16Cache($adapter);
        }

        return static::$instances[$name];
    }

    /**
     * 获取Redis驱动的缓存
     * @return Psr16Cache
     */
    public static function redis(): Psr16Cache
    {
        return static::store('redis');
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::store()->{$name}(... $arguments);
    }
}
