<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;

/**
 * pthome
 */
class DriverPthome extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'pthome';

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        if ($rss_url = $this->getConfig()->get('options.rss_url')) {
            return $rss_url;
        }
        return str_replace('{passkey}', $this->getConfig()->get('options.passkey', ''), RouteEnum::N2->value);
    }
}
