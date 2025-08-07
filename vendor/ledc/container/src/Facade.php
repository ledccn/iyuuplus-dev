<?php

namespace Ledc\Container;

/**
 * Facade管理类
 */
class Facade
{
    /**
     * 始终创建新的对象实例
     * @var bool
     */
    protected static bool $alwaysNewInstance = false;

    /**
     * 创建Facade实例
     * @param string $class 类名或标识
     * @param array $vars 变量
     * @param bool $newInstance 是否每次创建新的实例
     * @return object
     */
    protected static function createFacade(string $class = '', array $vars = [], bool $newInstance = false): object
    {
        $class = $class ?: static::class;

        $facadeClass = static::getFacadeClass();
        if ($facadeClass) {
            $class = $facadeClass;
        }

        if (static::$alwaysNewInstance) {
            $newInstance = true;
        }

        return App::getInstance()->make($class, $vars, $newInstance);
    }

    /**
     * 获取当前Facade对应类名
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return '';
    }

    /**
     * 带参数实例化当前Facade类
     * @access public
     * @param mixed ...$args
     * @return object
     */
    public static function instance(...$args): object
    {
        if (self::class !== static::class) {
            return self::createFacade('', $args);
        }
        return self::createFacade(static::class, $args);
    }

    /**
     * 调用类的实例
     * @access public
     * @param string $class 类名或者标识
     * @param true|array $args 变量
     * @param bool $newInstance 是否每次创建新的实例
     * @return object
     */
    public static function make(string $class, true|array $args = [], bool $newInstance = false): object
    {
        if (self::class !== static::class) {
            return self::__callStatic('make', func_get_args());
        }

        if (true === $args) {
            // 总是创建新的实例化对象
            $newInstance = true;
            $args = [];
        }

        return self::createFacade($class, $args, $newInstance);
    }

    /**
     * 调用实际类的方法
     * @param string $method
     * @param array $params
     * @return mixed
     */
    public static function __callStatic(string $method, array $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}
