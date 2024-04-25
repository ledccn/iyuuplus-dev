<?php

namespace app\model\enums;

/**
 * 自动辅种业务子类型枚举类
 */
enum ReseedSubtypeEnums: int
{
    /**
     * 自动辅种
     */
    case Default = 0;
    /**
     * 自动下载
     */
    case Downloader = 1;
    /**
     * 自动转移
     */
    case Transfer = 2;

    /**
     * 枚举的文本描述
     * @param self $enum
     * @return string
     */
    public static function text(self $enum): string
    {
        return match ($enum) {
            self::Default => '自动辅种',
            self::Downloader => '自动下载',
            self::Transfer => '自动转移',
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
