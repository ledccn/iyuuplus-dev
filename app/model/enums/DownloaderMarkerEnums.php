<?php

namespace app\model\enums;

/**
 * 下载器标记规则枚举类
 */
enum DownloaderMarkerEnums: string
{
    /**
     * 空置操作
     */
    case Empty = 'empty';
    /**
     * 标记标签
     */
    case Tag = 'tag';
    /**
     * 标记分类
     */
    case Category = 'category';

    /**
     * 枚举的文本描述
     * @param self $enum
     * @return string
     */
    public static function text(self $enum): string
    {
        return match ($enum) {
            self::Empty => '空置操作',
            self::Tag => '标记标签',
            self::Category => '标记分类',
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
