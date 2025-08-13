<?php

namespace Iyuu\SiteManager\Cache;

/**
 * 用户站点签名缓存
 */
class UserSiteSignatureCache extends BaseCache
{
    /**
     * 构造函数
     * @param string $site
     */
    public function __construct(string $site = '')
    {
        $this->setKey($site);
    }
}
