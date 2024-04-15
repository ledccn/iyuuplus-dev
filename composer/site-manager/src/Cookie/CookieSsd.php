<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * ssd
 * - 凭cookie解析HTML列表页
 */
class CookieSsd extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'ssd';

    /**
     * 解析副标题节点值【Crawler】
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        return $node->filterXPath("//div[contains(@class,'torrent-smalldescr')]")->text('');
    }
}
