<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;

/**
 * icc2022
 * - 凭cookie解析HTML列表页
 */
class CookieIcc2022 extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'icc2022';
}
