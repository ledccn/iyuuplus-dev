<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;

/**
 * siqi
 * - 凭cookie解析HTML列表页
 */
class CookieSiqi extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'siqi';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return false;
    }
}
