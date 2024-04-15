<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * pttime
 * - 凭cookie解析HTML列表页
 */
class CookiePttime extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'pttime';

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = str_contains($node->html(), 'class="torrentimg') ? $node->filterXPath('//td')->eq(1) : $node->filterXPath('//td')->first();
        return $first->filterXPath('//font')->text('');
    }
}
