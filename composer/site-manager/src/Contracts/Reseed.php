<?php

namespace Iyuu\SiteManager\Contracts;

/**
 * 辅种计算种子特征码
 */
interface Reseed
{
    /**
     * 契约方法
     * @param string $metadata
     * @return array
     */
    public static function reseed(string $metadata): array;
}
