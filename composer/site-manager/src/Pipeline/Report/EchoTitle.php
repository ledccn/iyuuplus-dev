<?php

namespace Iyuu\SiteManager\Pipeline\Report;

use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\Pipeline;
use Iyuu\SiteManager\Spider\Payload;
use Iyuu\SiteManager\Spider\SpiderWorker;

/**
 * 控制台显示种子信息
 */
class EchoTitle implements Pipeline
{
    /**
     * @param Payload $payload
     * @param callable $next
     * @return mixed
     */
    public static function process(Payload $payload, callable $next): mixed
    {
        $torrent = $payload->spiderTorrents;
        $id = $torrent->id ?? '';
        if (empty($id) || false === ctype_digit((string)$id)) {
            throw new InvalidArgumentException(sprintf('【%s】种子ID非数字', SpiderWorker::getParams()->site));
        }

        if (!SpiderWorker::getParams()->daemon) {
            $body = [
                '主标题：' . $torrent->h1 ?? '',
                '副标题：' . $torrent->title ?? '',
                '详情页：' . $torrent->details ?? '',
                '种子链接：' . $torrent->download ?? '',
            ];
            echo implode(PHP_EOL, $body) . PHP_EOL;
        }

        return $next($payload);
    }
}
