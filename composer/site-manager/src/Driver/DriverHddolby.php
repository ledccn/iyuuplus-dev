<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;

/**
 * hddolby
 */
class DriverHddolby extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'hddolby';

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
            '{uid}' => $this->getConfig()->getUid(),
            '{hash}' => $this->getConfig()->getDownHash(),
            '{downhash}' => $this->getConfig()->getDownHash(),
            '{passkey}' => $this->getConfig()->getPasskey()
        ];
    }
}
