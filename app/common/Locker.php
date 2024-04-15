<?php

namespace app\common;

use support\Container;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

/**
 * 业务锁
 * @method static SharedLockInterface locker(string $key, ?float $ttl = null, ?bool $autoRelease = null) 示例锁
 * @link https://symfony.com/doc/current/components/lock.html
 */
class Locker
{
    /**
     * 配置
     * @var array|null
     */
    protected static ?array $defaultConfig = null;
    /**
     * 锁工厂实例
     * @var LockFactory|null
     */
    protected static ?LockFactory $factory = null;

    /**
     * @param string $method
     * @param array $arguments
     * @return SharedLockInterface
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $class = str_replace('::', '_', static::class);
        $key = $arguments[0] ?? 'empty';
        unset($arguments[0]);
        return static::createLock(md5($class . ':' . $method . ':') . $key, ...$arguments);
    }

    /**
     * 创建锁
     * @param string $key 锁的key
     * @param float|null $ttl 默认锁超时时间（为null时取配置文件内的值）
     * @param bool|null $autoRelease 是否自动释放，建议设置为 true（为null时取配置文件内的值）
     * @return SharedLockInterface
     */
    final protected static function createLock(string $key, ?float $ttl = null, ?bool $autoRelease = null): SharedLockInterface
    {
        if (null === static::$defaultConfig) {
            static::$defaultConfig = config('lock.default_config', []);
        }
        $config = static::$defaultConfig;
        $ttl = $ttl ?? $config['ttl'] ?? 300;
        $autoRelease = $autoRelease ?? $config['auto_release'] ?? true;
        $prefix = $config['prefix'] ?? 'lock_';
        return static::getLockFactory()->createLock($prefix . $key, $ttl, $autoRelease);
    }

    /**
     * @return LockFactory
     */
    final protected static function getLockFactory(): LockFactory
    {
        if (null === static::$factory) {
            $storage = config('lock.storage');
            $storageConfig = config('lock.storage_configs')[$storage];
            if (is_callable($storageConfig['construct'])) {
                $storageConfig['construct'] = call_user_func($storageConfig['construct']);
            }
            $storageInstance = Container::make($storageConfig['class'], $storageConfig['construct']);
            static::$factory = new LockFactory($storageInstance);
        }

        return static::$factory;
    }
}
