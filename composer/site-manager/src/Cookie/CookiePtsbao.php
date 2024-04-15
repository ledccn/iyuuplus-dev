<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * ptsbao
 * - 凭cookie解析HTML列表页
 */
class CookiePtsbao extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'ptsbao';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return false;
    }

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = $node->filterXPath('//td')->first();
        $temp = explode('<br>', $first->html());
        return count($temp) === 2 ? preg_replace("/<([a-zA-Z]+)[^>]*>/", "<\\1>", $temp[1]) : '';
    }
}
