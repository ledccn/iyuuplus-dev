<?php

namespace Iyuu\SiteManager\Pipeline\Report;

use Iyuu\SiteManager\Contracts\Pipeline;
use Iyuu\SiteManager\Exception\BadRequestException;
use Iyuu\SiteManager\Exception\EmptyMetadataException;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Spider\Payload;
use Iyuu\SiteManager\Spider\SpiderClient;

/**
 * 发起查询请求
 */
class RequestFind implements Pipeline
{
    /**
     * @param Payload $payload
     * @param callable $next
     * @return mixed
     * @throws BadRequestException
     * @throws TorrentException
     */
    public static function process(Payload $payload, callable $next): mixed
    {
        $sites = $payload->baseDriver;
        $torrent = $payload->spiderTorrents;
        $client = SpiderClient::getInstance();
        $client->findTorrent($sites->getConfig()->site, $torrent->id);

        $response = $sites->downloadMetadata($torrent);
        //检查种子元数据
        if (empty($response)) {
            throw new EmptyMetadataException('种子元数据为空');
        }
        $torrent->metadata = $response;

        return $next($payload);
    }
}
