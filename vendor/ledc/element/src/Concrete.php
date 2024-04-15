<?php

namespace Ledc\Element;

/**
 * 默认实现者
 */
class Concrete implements GenerateInterface
{
    /**
     * 输出HTML
     * @return string
     */
    public function html(): string
    {
        return PHP_EOL;
    }

    /**
     * 输出JavaScript
     * @return string
     */
    public function js(): string
    {
        return PHP_EOL;
    }
}
