<?php

namespace plugin\cron\app\services\generates;

use plugin\cron\app\interfaces\CrontabTaskTypeEnumsInterface;

/**
 * 计划任务类型
 */
enum CrontabTaskTypeEnums: int implements CrontabTaskTypeEnumsInterface
{
    /**
     * webman命令
     */
    case command = 1;

    /**
     * 执行类方法
     */
    case classMethod = 2;

    /**
     * 访问URL
     */
    case urlRequest = 3;

    /**
     * eval执行PHP代码
     */
    case evalCode = 4;

    /**
     * shell脚本
     */
    case shellExec = 5;

    /**
     * 枚举的文本描述
     * @param self $enum
     * @return string
     */
    public static function text(self $enum): string
    {
        return match ($enum) {
            self::command => 'webman命令',
            self::classMethod => '执行类方法',
            self::urlRequest => '访问URL',
            self::evalCode => 'eval执行PHP代码',
            self::shellExec => 'shell脚本',
        };
    }

    /**
     * 枚举条目转为数组
     * - 文本描述 => 值
     * @return array
     */
    public static function select(): array
    {
        $rs = [];
        foreach (self::cases() as $enum) {
            $rs[self::text($enum)] = $enum->value;
        }
        return $rs;
    }

    /**
     * 枚举条目转为数组
     * - 名 => 值
     * @return array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }
}
