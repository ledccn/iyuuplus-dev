<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;

/**
 * yemapt
 */
class DriverYemapt extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'yemapt';

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '/torrent\/detail\/(\d+)\//i';
    }
}
