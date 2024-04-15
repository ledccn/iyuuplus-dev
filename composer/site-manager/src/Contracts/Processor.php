<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\Exception\EmptyListException;

/**
 * 凭cookie解析HTML列表页
 * - 解析列表页，生成列表数据
 * - 解析页面中待抓取链接，存入调度器
 */
interface Processor
{
    /**
     * 契约方法
     * - 凭cookie解析HTML列表页
     * @param string $url 列表URL
     * @return array
     * @throws EmptyListException
     */
    public function process(string $url): array;
}
