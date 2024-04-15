<?php

namespace plugin\cron\app\interfaces;

/**
 * 第一步：计划任务类型枚举
 */
interface CrontabTaskTypeEnumsInterface
{
    /**
     * 枚举条目转为数组
     * - 文本描述 => 值
     * @return array
     */
    public static function select(): array;
}
