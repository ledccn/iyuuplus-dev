<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;

/**
 * opencd
 * - 凭cookie解析HTML列表页
 */
class CookieOpencd extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'opencd';
}
