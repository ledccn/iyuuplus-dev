<?php

namespace Ledc\Container;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use LogicException;
use think\helper\Str;

/**
 * 管理器
 */
abstract class Manager
{
    /**
     * 已注册的自定义驱动创建者
     * - The registered custom driver creators.
     * @var array|array<string, Closure>
     */
    protected array $customCreators = [];
    /**
     * 已缓存的驱动对象实例
     * @var array|array<string, object>
     */
    protected array $drivers = [];
    /**
     * 驱动的命名空间
     * @var string|null
     */
    protected ?string $namespace = null;
    /**
     * 始终创建新的驱动对象实例
     * @var bool
     */
    protected bool $alwaysNewInstance = false;

    /**
     * 默认驱动
     * @return string|null
     */
    abstract public function getDefaultDriver(): ?string;

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     */
    protected function driver(?string $name = null): mixed
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        // 始终创建新的驱动对象实例
        if ($this->alwaysNewInstance) {
            return $this->createDriver($name);
        }

        // 如果之前尚未创建给定的驱动实例，则创建并缓存它，以便下次快速返回
        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        // 返回已缓存的驱动对象实例
        return $this->drivers[$name];
    }

    /**
     * 注册自定义驱动创建者闭包
     * - Register a custom driver creator Closure.
     * @param string $driver
     * @param Closure $callback
     * @return static
     */
    final public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return string
     */
    protected function resolveType(string $name): string
    {
        return $name;
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     */
    protected function resolveConfig(string $name): mixed
    {
        return $name;
    }

    /**
     * 获取驱动参数
     * @param string $name
     * @return array
     */
    protected function resolveParams(string $name): array
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     */
    final protected function resolveClass(string $type): string
    {
        if ($this->namespace || str_contains($type, '\\')) {
            $class = str_contains($type, '\\') ? $type : $this->namespace . Str::studly($type);

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

    /**
     * 创建驱动
     * @param string $name
     * @return mixed
     *
     */
    final protected function createDriver(string $name): mixed
    {
        $type = $this->resolveType($name);
        $params = $this->resolveParams($name);

        // 已注册的自定义驱动创建者
        if (isset($this->customCreators[$type])) {
            return static::app()->invokeFunction($this->customCreators[$type], $params);
        }

        // 从方法创建
        $method = 'create' . Str::studly($type) . 'Driver';
        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        // 从容器创建
        $abstract = $this->alwaysNewInstance ? $type : $name;
        if (static::app()->bound($abstract)) {
            return static::app()->make($abstract, $params, $this->alwaysNewInstance);
        }

        // 从命名空间创建
        $class = $this->resolveClass($type);
        return static::app()->invokeClass($class, $params);
    }

    /**
     * 移除一个驱动实例
     * @param array|string|null $name
     * @return static
     */
    final public function forgetDriver(array|string|null $name = null): static
    {
        $name ??= $this->getDefaultDriver();

        foreach ((array)$name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 清理所有驱动实例
     * @return void
     */
    final public function clearDrivers(): void
    {
        $keys = array_keys($this->drivers);
        foreach ($keys as $key) {
            unset($this->drivers[$key]);
        }
    }

    /**
     * 获取所有驱动实例
     * @return array
     */
    final public function getDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * 获取容器中的对象实例 不存在则创建（单例模式）
     * @return static
     */
    final public static function getInstance(): static
    {
        if (self::class === static::class) {
            throw new LogicException('请使用子类调用 getInstance() 方法');
        }
        return App::pull(static::class);
    }

    /**
     * 获取容器实例
     * @return App
     */
    final public static function app(): App
    {
        return App::getInstance();
    }

    /**
     * 动态调用
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }

    /**
     * 在静态上下文中调用一个不可访问方法时，__callStatic() 会被调用
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $driver = static::getInstance()->driver();
        if (is_callable([$driver, $name])) {
            return $driver->{$name}(... $arguments);
        }
        throw new BadMethodCallException('未定义的方法：' . $name);
    }
}
