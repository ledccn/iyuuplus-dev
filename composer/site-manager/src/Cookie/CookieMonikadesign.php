<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\Unit3D\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;

/**
 * monikadesign
 * - 凭cookie解析HTML列表页
 */
class CookieMonikadesign extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'monikadesign';
}
