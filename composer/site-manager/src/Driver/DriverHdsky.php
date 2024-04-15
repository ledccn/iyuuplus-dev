<?php

namespace Iyuu\SiteManager\Driver;

use Error;
use Exception;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Ledc\Curl\Curl;
use Throwable;

/**
 * hdsky
 */
class DriverHdsky extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdsky';

    /**
     * 生成下载种子的完整的URL
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $domain = $this->getConfig()->parseDomain();
            $uri = $this->getConfig()->parseDetailUri();
            $url_replace = $this->parseReplace($torrent);
            $uri = strtr($uri, $url_replace);
            $curl = new Curl();
            $this->getConfig()->setCurlOptions($curl);
            $curl->setCookies($this->getConfig()->get('cookie'));
            $curl->get($domain . '/' . $uri);
            if ($curl->isSuccess()) {
                $torrent_id = $torrent->torrent_id;
                if (preg_match("#download.php\?id={$torrent_id}(?:&|&amp;)passkey=([A-Za-z0-9]{32})(?:&|&amp;)sign=([A-Za-z0-9]{32})#i", $curl->response, $matches)) {
                    $torrent->setDownload($domain . '/' . str_replace('&amp;', '&', $matches[0]), false);
                    return $torrent->download;
                }
            }

            $this->throwException($curl);
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }
}
