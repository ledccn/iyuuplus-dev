<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Utils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * agsvpt
 * - 凭cookie解析HTML列表页
 */
class CookieAgsvpt extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'agsvpt';

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = $node->filterXPath('//td')->eq(1)->filterXPath("//div[contains(@class,'torrent_title_desc')]");
        return $first->count() ? Utils::regexRemove('#.*</span>#ims', $first->html('')) : '';
    }
}
