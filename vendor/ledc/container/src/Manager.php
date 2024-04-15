<?php

namespace Ledc\Container;

use InvalidArgumentException;
use think\helper\Str;

/**
 * 管理器
 */
abstract class Manager
{
    /**
     * App实例
     * @var App
     */
    protected App $app;

    /**
     * 驱动
     * @var array
     */
    protected array $drivers = [];

    /**
     * 驱动的命名空间
     * @var string|null
     */
    protected ?string $namespace = null;

    /**
     * 构造函数
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

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
    protected function driver(string $name = null): mixed
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->drivers[$name] = $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @return mixed
     */
    protected function getDriver(string $name): mixed
    {
        return $this->drivers[$name] ?? $this->createDriver($name);
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
     * 获取驱动类
     * @param string $type
     * @return string
     */
    protected function resolveClass(string $type): string
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
     * 创建驱动
     * @param string $name
     * @return mixed
     *
     */
    protected function createDriver(string $name): mixed
    {
        $type = $this->resolveType($name);
        $params = $this->resolveParams($name);

        // 从方法创建
        $method = 'create' . Str::studly($type) . 'Driver';
        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        // 从容器创建
        if ($this->app->bound($name)) {
            $newInstance = true;
            if (property_exists($this, 'newInstance')) {
                $newInstance = $this->newInstance;
            }
            return $this->app->make($name, $params, $newInstance);
        }

        // 从命名空间创建
        $class = $this->resolveClass($type);
        return $this->app->invokeClass($class, $params);
    }

    /**
     * 移除一个驱动实例
     * @param array|string|null $name
     * @return static
     */
    public function forgetDriver(array|string $name = null): static
    {
        $name = $name ?? $this->getDefaultDriver();

        foreach ((array)$name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
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
}
