<?php

namespace app\traits;

use Webman\Event\Event;

/**
 * 事件注册
 */
trait HasEventRegister
{
    /**
     * 注册事件
     * @param callable $listener
     * @return int
     */
    public function register(callable $listener): int
    {
        return Event::on($this->value, $listener);
    }
}
