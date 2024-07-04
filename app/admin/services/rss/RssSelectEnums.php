<?php

namespace app\admin\services\rss;

/**
 * RSS订阅枚举类
 */
enum RssSelectEnums: int
{
    /**
     * webman命令
     */
    case rss = 12;

    /**
     * 枚举的文本描述
     * @param self $enum
     * @return string
     */
    public static function text(self $enum): string
    {
        return match ($enum) {
            self::rss => 'RSS订阅',
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
