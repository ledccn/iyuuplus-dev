<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;

/**
 * greatposterwall
 */
class DriverGreatposterwall extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'greatposterwall';

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
            '{authkey}' => $this->getConfig()->getOptions('authkey'),
            '{torrent_pass}' => $this->getConfig()->getOptions('torrent_pass'),
        ];
    }
}
