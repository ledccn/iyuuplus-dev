<?php

namespace Iyuu\SiteManager\Cache;

/**
 * 种子特征码查询缓存
 */
class TorrentFindCache extends BaseCache
{
    /**
     * 构造函数
     * @param string $site
     * @param string $torrent_id
     */
    public function __construct(string $site = '', string $torrent_id = '')
    {
        $this->setKey($site . '_' . $torrent_id);
    }
}
