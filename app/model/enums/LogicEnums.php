<?php

namespace app\model\enums;

/**
 * 逻辑关系
 */
enum LogicEnums
{
    /**
     * 逻辑或
     */
    case OR;
    /**
     * 逻辑与
     */
    case AND;

    /**
     * 创建枚举
     * @param string $name
     * @return self
     */
    public static function create(string $name): self
    {
        return match (strtoupper($name)) {
            self::AND->name => self::AND,
            default => self::OR,
        };
    }
}
