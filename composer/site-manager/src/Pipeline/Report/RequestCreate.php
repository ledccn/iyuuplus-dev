<?php

namespace Iyuu\SiteManager\Pipeline\Report;

use Iyuu\SiteManager\Contracts\Pipeline;
use Iyuu\SiteManager\Contracts\Reseed;
use Iyuu\SiteManager\Exception\BadRequestException;
use Iyuu\SiteManager\Spider\Payload;
use Iyuu\SiteManager\Spider\SpiderClient;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use RuntimeException;
use Throwable;

/**
 * 上报种子元数据
 */
class RequestCreate implements Pipeline
{
    /**
     * @param Payload $payload
     * @param callable $next
     * @return mixed
     * @throws BadRequestException|Throwable
     */
    public static function process(Payload $payload, callable $next): mixed
    {
        $sites = $payload->baseDriver;
        $torrent = $payload->spiderTorrents;
        $metadata = $torrent->metadata;
        if (SpiderTorrents::existsDecoder()) {
            /** @var Reseed $decoder */
            $decoder = SpiderTorrents::DECODER;
            $data = $decoder::reseed($metadata);

            $client = SpiderClient::getInstance();
            $client->createTorrent($sites->getConfig()->site, $torrent, $data);

            return $next($payload);
        } else {
            throw new RuntimeException('默认的种子解码器不存在或未实现契约');
        }
    }
}
