<?php

namespace Ledc\Macroable;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * 宏指令
 * - 来源 composer包：illuminate/macroable
 */
trait Macroable
{
    /**
     * 注册的字符串宏指令.
     * @var array
     */
    protected static array $macros = [];

    /**
     * 注册宏指令到类
     * - 动态的添加方法到类
     * @param string $name 宏名称
     * @param callable|object $macro 可调用的值或实现__invoke的对象
     * @return void
     */
    public static function macro(string $name, callable|object $macro): void
    {
        static::$macros[$name] = $macro;
    }

    /**
     * 将另一个对象的方法添加到当前类中
     * Mix another object into the class.
     * @param object $mixin
     * @param bool $replace
     * @return void
     * @throws ReflectionException
     */
    public static function mixin(object $mixin, bool $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || !static::hasMacro($method->name)) {
                static::macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * 检查是否注册了宏指令.
     * @param string $name 宏名称
     * @return bool
     */
    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    /**
     * 清空宏指令
     * @return void
     */
    public static function flushMacros(): void
    {
        static::$macros = [];
    }

    /**
     * 魔法方法
     * - 动态的处理对类的调用
     *
     * @param string $method 宏名称
     * @param array $parameters
     * @return mixed
     * @throws BadMethodCallException
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * 魔法方法
     * - 动态的处理对类的调用
     *
     * @param string $method 宏名称
     * @param array $parameters
     * @return mixed
     * @throws BadMethodCallException
     */
    public function __call(string $method, array $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }

        $macro = static::$macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}
