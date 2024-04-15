<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;

/**
 * audiences
 */
class DriverAudiences extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'audiences';

    /**
     * 解析生成替换规则
     * @param Torrent $torrent
     * @return array
     */
    protected function parseReplace(Torrent $torrent): array
    {
        return [
            '{}' => $torrent->torrent_id,
            '{id}' => $torrent->torrent_id,
            '{downHash}' => $this->getConfig()->get('options.rsskey', '')
        ];
    }

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        return str_replace('{rsskey}', $this->getConfig()->get('options.rsskey', ''), RouteEnum::N6->value);
    }
}
