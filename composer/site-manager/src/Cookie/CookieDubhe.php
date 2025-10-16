<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;

/**
 * dubhe
 * - 凭cookie解析HTML列表页
 */
class CookieDubhe extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'dubhe';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return false;
    }
}
