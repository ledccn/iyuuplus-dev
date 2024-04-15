<?php

namespace Ledc\Macroable;

use BadMethodCallException;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * 宏指令
 */
trait Macro
{
    /**
     * 注册的字符串宏指令.
     * @var array
     */
    protected array $macros = [];

    /**
     * 注册宏指令到当前对象
     * - 动态的添加方法到类
     * @param string $name 宏名称
     * @param callable|object $macro 可调用的值或实现__invoke的对象
     * @return void
     */
    public function macro(string $name, callable|object $macro): void
    {
        $this->macros[$name] = $macro;
    }

    /**
     * 批量注册宏指令到当前对象
     * @param array $names
     * @param callable|object $macro
     * @return void
     */
    public function macros(array $names, callable|object $macro): void
    {
        foreach ($names as $name) {
            $this->macro($name, $macro);
        }
    }

    /**
     * 将另一个对象的方法添加到当前类中
     * Mix another object into the class.
     * @param object $mixin
     * @param bool $replace
     * @return void
     * @throws ReflectionException
     */
    public function mixin(object $mixin, bool $replace = true): void
    {
        $methods = (new ReflectionClass($mixin))->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ($methods as $method) {
            if ($replace || !$this->hasMacro($method->name)) {
                $this->macro($method->name, $method->invoke($mixin));
            }
        }
    }

    /**
     * 检查是否注册了宏指令.
     * @param string $name 宏名称
     * @return bool
     */
    public function hasMacro(string $name): bool
    {
        return isset($this->macros[$name]);
    }

    /**
     * 清空宏指令
     * @return void
     */
    public function flushMacros(): void
    {
        $this->macros = [];
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
        if (!$this->hasMacro($method)) {
            throw new BadMethodCallException(sprintf(
                'Method %s does not exist.', $method
            ));
        }

        $macro = $this->macros[$method];

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo($this, static::class);
        }

        return $macro(...$parameters);
    }
}