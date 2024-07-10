<?php

namespace app\admin\services\rss;

use app\common\Number;
use app\model\enums\SizeUnitEnums;

/**
 * 种子大小逻辑
 */
class SizeLogic
{
    /**
     * 种子大小：最小值
     * @var string
     */
    protected string $size_min;
    /**
     * 种子大小：最小值单位
     * @var SizeUnitEnums
     */
    protected SizeUnitEnums $size_min_unit;
    /**
     * 种子大小：最大值
     * @var string
     */
    protected string $size_max;
    /**
     * 种子大小：最大值单位
     * @var SizeUnitEnums
     */
    protected SizeUnitEnums $size_max_unit;

    /**
     * 构造函数
     * @param string $size_min 最小值
     * @param string $size_min_unit 最小值单位
     * @param string $size_max 最大值
     * @param string $size_max_unit 最大值单位
     */
    public function __construct(string $size_min, string $size_min_unit, string $size_max, string $size_max_unit)
    {
        $this->size_min = $size_min;
        $this->size_min_unit = SizeUnitEnums::from($size_min_unit);
        $this->size_max = $size_max;
        $this->size_max_unit = SizeUnitEnums::from($size_max_unit);
    }

    /**
     * 匹配
     * @param TorrentItem $item
     * @return bool
     */
    public function match(TorrentItem $item): bool
    {
        $length = (string)$item->getLength();
        if (empty($length)) {
            return true;
        }

        if (empty($this->size_min) && empty($this->size_max)) {
            return true;
        }

        return match (true) {
            empty($this->size_min) => $this->bccompSizeMin($length),
            empty($this->size_max) => $this->bccompSizeMax($length),
            default => $this->bccompSizeMin($length) and $this->bccompSizeMax($length),
        };
    }

    /**
     * 比较最小值
     * @param string $length
     * @return bool
     */
    private function bccompSizeMin(string $length): bool
    {
        return -1 === Number::bccomp(SizeUnitEnums::convert($this->size_min, $this->size_min_unit), $length);
    }

    /**
     * 比较最大值
     * @param string $length
     * @return bool
     */
    private function bccompSizeMax(string $length): bool
    {
        return -1 === Number::bccomp($length, SizeUnitEnums::convert($this->size_max, $this->size_max_unit));
    }
}
