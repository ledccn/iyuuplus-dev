<?php

namespace app\common\cache;

use support\Cache;
use Symfony\Component\Cache\Psr16Cache;

/**
 * 缓存基础类
 * @method iterable getMultiple($keys, $default = null)
 * @method bool setMultiple($values, $ttl = null)
 * @method bool deleteMultiple($keys)
 */
abstract class BaseCache
{
    /**
     * 缓存KEY
     * @var string
     */
    private string $key;

    /**
     * 构造函数
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }

    /**
     * 获取缓存
     * @param mixed|null $default
     * @return mixed
     */
    final public function get(mixed $default = null): mixed
    {
        return Cache::get($this->getKey(), $default);
    }

    /**
     * 设置缓存
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    final public function set(mixed $value, int $ttl = null): bool
    {
        return Cache::set($this->getKey(), $value, $ttl);
    }

    /**
     * 删除缓存
     * @return bool
     */
    final public function delete(): bool
    {
        return Cache::delete($this->getKey());
    }

    /**
     * 清空全部缓存
     * @return bool
     */
    final public function clear(): bool
    {
        return Cache::clear();
    }

    /**
     * 是否存在缓存
     * @return bool
     */
    final public function has(): bool
    {
        return Cache::has($this->getKey());
    }

    /**
     * 设置缓存KEY
     * @param string $key
     */
    final public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * 获取缓存KEY
     * @return string
     */
    final public function getKey(): string
    {
        return md5(get_class($this)) . $this->key;
    }

    /**
     * 缓存句柄对象
     * @return Psr16Cache
     */
    final public static function instance(): Psr16Cache
    {
        return Cache::store();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    final public function __call(string $name, array $arguments): mixed
    {
        return Cache::store()->{$name}(... $arguments);
    }
}
