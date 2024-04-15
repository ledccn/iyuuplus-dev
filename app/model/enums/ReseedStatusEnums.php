<?php

namespace app\model\enums;

/**
 * 自动辅种状态枚举
 */
enum ReseedStatusEnums: int
{
    /**
     * 待辅种
     */
    case Default = 0;
    /**
     * 成功
     */
    case Success = 1;
    /**
     * 失败
     */
    case Fail = 2;

    /**
     * 枚举的文本描述
     * @param self $enum
     * @return string
     */
    public static function text(self $enum): string
    {
        return match ($enum) {
            self::Default => '待辅种',
            self::Success => '成功',
            self::Fail => '失败',
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
