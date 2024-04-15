<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\Exception\TorrentException;

/**
 * 获取下载种子完整URL的接口
 */
interface DownloaderLinkInterface
{
    /**
     * 生成下载种子的完整的URL
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string;
}
