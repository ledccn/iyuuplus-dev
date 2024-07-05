<?php

namespace app\model\enums;

/**
 * 容量大小单位
 */
enum SizeUnitEnums: string
{
    /**
     * 字节
     */
    case B = 'B';
    /**
     * 千字节
     */
    case KB = 'KB';
    /**
     * 兆字节
     */
    case MB = 'MB';
    /**
     * 千兆字节
     */
    case GB = 'GB';
    /**
     * 太字节
     */
    case TB = 'TB';
    /**
     * 拍字节
     */
    case PB = 'PB';
    /**
     * 艾字节
     */
    case EB = 'EB';
    /**
     * 泽字节
     */
    case ZB = 'ZB';
    /**
     * 尧字节
     */
    case YB = 'YB';

    /**
     * 转换为字节
     * @param string $value
     * @param SizeUnitEnums $enums
     * @return string
     */
    public static function convert(string $value, self $enums): string
    {
        return match ($enums) {
            self::B => $value,
            self::KB => bcmul($value, (string)pow(1024, 1)),
            self::MB => bcmul($value, (string)pow(1024, 2)),
            self::GB => bcmul($value, (string)pow(1024, 3)),
            self::TB => bcmul($value, (string)pow(1024, 4)),
            self::PB => bcmul($value, (string)pow(1024, 5)),
            self::EB => bcmul($value, (string)pow(1024, 6)),
            self::ZB => bcmul($value, (string)pow(1024, 7)),
            self::YB => bcmul($value, (string)pow(1024, 8)),
        };
    }
}
