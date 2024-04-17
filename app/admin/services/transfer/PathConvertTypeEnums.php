<?php

namespace app\admin\services\transfer;

/**
 * 路径转换类型枚举类
 */
enum PathConvertTypeEnums: string
{
    /**
     * 相等
     */
    case Eq = 'eq';
    /**
     * 减
     */
    case Sub = 'sub';
    /**
     * 加
     */
    case Add = 'add';
    /**
     * 替换
     */
    case Replace = 'replace';
}
