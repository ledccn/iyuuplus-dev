<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Symfony\Component\DomCrawler\Crawler;

/**
 * dmhy
 * - 凭cookie解析HTML列表页
 */
class CookieDmhy extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'dmhy';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return false;
    }

    /**
     * 获取主标题
     * @param Crawler|string $node
     * @return string
     */
    protected function parseH1Node(Crawler|string $node): string
    {
        if (is_string($node)) {
            $node = new Crawler($node);
        }
        return $node->filterXPath('//a')->first()->text('');
    }

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        return '';
    }
}
