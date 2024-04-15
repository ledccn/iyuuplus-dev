<?php

namespace Iyuu\PacificSdk;

/**
 * 从数组赋值当前类的属性
 */
trait Properties
{
    /**
     * 构造函数
     * @param array $properties 原始数据
     */
    public function __construct(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }
}
