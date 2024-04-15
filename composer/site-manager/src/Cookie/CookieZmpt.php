<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * zmpt
 * - 凭cookie解析HTML列表页
 */
class CookieZmpt extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'zmpt';

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = $node->filterXPath('//td')->first();
        return Selector::remove($first->html(), '/(.*?)(?:<br>|<\/div>|<\/span>)/ims', 'regex');
    }
}
