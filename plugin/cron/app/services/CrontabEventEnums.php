<?php

namespace plugin\cron\app\services;

/**
 * 计划任务事件枚举
 */
enum CrontabEventEnums
{
    /**
     * 数据创建后
     */
    case created;

    /**
     * 数据更新后
     */
    case updated;

    /**
     * 数据删除后
     */
    case deleted;

    /**
     * 手动运行
     */
    case start;

    /**
     * 手动停止
     */
    case stop;

    /**
     * 所有枚举值
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'name');
    }
}
