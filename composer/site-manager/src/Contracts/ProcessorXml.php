<?php

namespace Iyuu\SiteManager\Contracts;

/**
 * Rss解析接口
 */
interface ProcessorXml
{
    /**
     * 契约方法
     * @param string $url RSS网址
     * @return array
     */
    public function processXml(string $url): array;
}
