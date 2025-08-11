<?php

namespace Iyuu\SiteManager\Pipeline\Report;

use Iyuu\SiteManager\Cache\TorrentFindCache;
use Iyuu\SiteManager\Contracts\Pipeline;
use Iyuu\SiteManager\Exception\BadRequestException;
use Iyuu\SiteManager\Exception\EmptyMetadataException;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Spider\Payload;
use Iyuu\SiteManager\Spider\SpiderClient;
use Iyuu\SiteManager\Utils;
use RuntimeException;

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
        // 优化：减少查询次数
        $cache = new TorrentFindCache($sites->getConfig()->site, (string)$torrent->id);
        if ($cache->has()) {
            echo $torrent->id .  ' 种子已存在，跳过查询。' . PHP_EOL . PHP_EOL;
            throw Utils::createTorrentExistsException();
        }
        try {
            $client = SpiderClient::getInstance();
            $client->findTorrent($sites->getConfig()->site, $torrent->id);
        } catch (RuntimeException $exception) {
            if ($exception->getCode() === SpiderClient::TORRENT_EXIST_CODE) {
                // 缓存30天
                $cache->set(time(), 86400 * 30);
            }
            throw $exception;
        }

        $response = $sites->downloadMetadata($torrent);
        //检查种子元数据
        if (empty($response)) {
            throw new EmptyMetadataException('种子元数据为空');
        }
        $torrent->metadata = $response;

        return $next($payload);
    }
}
