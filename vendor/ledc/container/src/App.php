<?php

namespace Ledc\Container;

use Closure;
use think\Container;

/**
 * 应用
 */
class App extends Container
{
    /**
     * 容器对象实例
     * @var App|Closure
     */
    protected static $instance;

    /**
     * 构造函数
     */
    public function __construct()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('think\Container', $this);
        $this->instance(App::class, $this);
    }
}
