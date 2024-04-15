<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Utils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * wintersakura
 * - 凭cookie解析HTML列表页
 */
class CookieWintersakura extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'wintersakura';

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = $node->filterXPath('//td')->eq(1);
        $temp = explode('<br>', $first->html());
        return count($temp) === 2 ? Utils::regexRemove('#.*</span>#ims', $temp[1]) : '';
    }
}
