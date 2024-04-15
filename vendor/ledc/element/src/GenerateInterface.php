<?php

namespace Ledc\Element;

/**
 * 生成器接口
 */
interface GenerateInterface
{
    /**
     * 输出HTML
     * @return string
     */
    public function html(): string;

    /**
     * 输出JavaScript
     * @return string
     */
    public function js(): string;
}
