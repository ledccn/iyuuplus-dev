<?php

namespace app\common;

/**
 * BC数学函数（自适应精度）
 */
class Number
{
    /**
     * 指定精度
     * @var int|null
     */
    public static ?int $scale = null;

    /**
     * 最大精度
     * @param string $num1
     * @param string $num2
     * @return int
     */
    public static function maxScale(string $num1, string $num2): int
    {
        return max(static::getScale($num1), static::getScale($num2));
    }

    /**
     * 获取数值精度
     * @param string $num
     * @return int
     */
    public static function getScale(string $num): int
    {
        if (str_contains($num, '.')) {
            return strlen(substr($num, strpos($num, '.') + 1));
        }
        return 0;
    }

    /**
     * 移除小数尾部0
     * @param string $result
     * @return string
     */
    public static function format(string $result): string
    {
        if (str_contains($result, '.')) {
            $result = rtrim($result, '0');
            if (str_ends_with($result, '.')) {
                return rtrim($result, '.');
            }
            return $result;
        }
        return $result;
    }

    /**
     * 比较两个任意精度的数字
     * - 两个数相等时返回 0； num1 比 num2 大时返回 1； 其他则返回 -1
     * @param string $num1
     * @param string $num2
     * @param int|null $scale
     * @return int
     */
    public static function bccomp(string $num1, string $num2, ?int $scale = null): int
    {
        return bccomp($num1, $num2, $scale ?? self::autoScale($num1, $num2));
    }

    /**
     * 返回两个数的最大精度
     * @param string $num1
     * @param string $num2
     * @return int
     */
    protected static function autoScale(string $num1, string $num2): int
    {
        if (null !== static::$scale) {
            return max(static::getScale($num1), static::getScale($num2), static::$scale);
        }
        return static::maxScale($num1, $num2);
    }

    /**
     * 两个任意精度数字的加法计算
     * @param string $num1
     * @param string $num2
     * @param int|null $scale 小数点后的小数位数
     * @return string
     */
    public static function bcadd(string $num1, string $num2, ?int $scale = null): string
    {
        return bcadd($num1, $num2, $scale ?? self::autoScale($num1, $num2));
    }

    /**
     * 两个任意精度数字的减法
     * @param string $num1
     * @param string $num2
     * @param int|null $scale
     * @return string
     */
    public static function bcsub(string $num1, string $num2, ?int $scale = null): string
    {
        return bcsub($num1, $num2, $scale ?? self::autoScale($num1, $num2));
    }

    /**
     * 两个任意精度数字乘法计算
     * @param string $num1
     * @param string $num2
     * @param int|null $scale
     * @return string
     */
    public static function bcmul(string $num1, string $num2, ?int $scale = null): string
    {
        return bcmul($num1, $num2, $scale ?? self::autoScale($num1, $num2));
    }

    /**
     * 两个任意精度的数字除法计算
     * @param string $num1
     * @param string $num2
     * @param int|null $scale
     * @return string|null 如果 num2 是 0 结果为 null
     */
    public static function bcdiv(string $num1, string $num2, ?int $scale = null): ?string
    {
        return bcdiv($num1, $num2, $scale ?? self::autoScale($num1, $num2));
    }

    /**
     * 任意精度数字的乘方
     * @param string $num 底数
     * @param string $exponent 指数
     * @param int|null $scale
     * @return string
     */
    public static function bcpow(string $num, string $exponent, ?int $scale = null): string
    {
        return bcpow($num, $exponent, $scale ?? self::autoScale($num, $exponent));
    }

    /**
     * 获取精度或者设置精度
     * - 设置所有 bc math 函数在未设定情况下的小数点保留位数
     * - 获取当前的小数点保留位数
     * @param int|null $scale
     * @return int
     */
    public static function bcscale(?int $scale = null): int
    {
        return bcscale($scale);
    }
}
