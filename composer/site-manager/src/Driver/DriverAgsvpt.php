<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;

/**
 * agsvpt
 */
class DriverAgsvpt extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'agsvpt';
}
