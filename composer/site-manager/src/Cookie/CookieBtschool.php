<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * btschool
 * - 凭cookie解析HTML列表页
 */
class CookieBtschool extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'btschool';

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
        return Selector::remove($first->html(), '/(.*?)>/ims', 'regex');
    }
}
