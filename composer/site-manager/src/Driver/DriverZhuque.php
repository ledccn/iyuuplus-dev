<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;

/**
 * zhuque
 */
class DriverZhuque extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'zhuque';

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '#torrent/info/(\d+)#i';
    }

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        if ($rss_url = $this->getConfig()->get('options.rss_url')) {
            return $rss_url;
        }

        $replace = [
            '{rss_key}' => $this->getConfig()->getOptions('rss_key'),
            '{torrent_key}' => $this->getConfig()->getOptions('torrent_key'),
        ];

        return strtr(RouteEnum::N10->value, $replace);
    }

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
            '{torrent_key}' => $this->getConfig()->getOptions('torrent_key'),
        ];
    }
}
