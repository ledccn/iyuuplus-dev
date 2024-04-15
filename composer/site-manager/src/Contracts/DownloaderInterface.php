<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\Exception\TorrentException;

/**
 * 下载种子的接口
 */
interface DownloaderInterface
{
    /**
     * 下载种子二进制文件
     * @param Torrent $torrent
     * @return mixed
     * @throws TorrentException
     */
    public function download(Torrent $torrent): Response;
}
