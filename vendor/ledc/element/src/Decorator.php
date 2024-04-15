<?php

namespace Ledc\Element;

use InvalidArgumentException;

/**
 * 生成器实现抽象类
 * - 装饰器模式
 */
abstract class Decorator implements GenerateInterface
{
    /**
     * 构造函数
     * @param GenerateInterface $generate
     */
    public function __construct(public readonly GenerateInterface $generate)
    {
    }

    /**
     * 创建实例
     * @param array $decorators
     * @param Concrete|GenerateInterface $concrete
     * @return GenerateInterface
     */
    final public static function make(array $decorators, Concrete|GenerateInterface $concrete): GenerateInterface
    {
        $generate = $concrete;
        foreach ($decorators as $decorator) {
            self::checkClass($decorator);
            $generate = new $decorator($generate);
        }
        return $generate;
    }

    /**
     * 检查class是否为Generator的子类
     * @param string $class
     * @return void
     */
    private static function checkClass(string $class): void
    {
        if (!is_subclass_of($class, Decorator::class, true)) {
            throw new InvalidArgumentException($class . '必须继承：' . Decorator::class);
        }
    }
}
