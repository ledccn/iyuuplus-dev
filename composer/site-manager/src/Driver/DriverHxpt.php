<?php

namespace Iyuu\SiteManager\Driver;

use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Frameworks\FluxTorrent\HasRss;

/**
 * hxpt
 * - 站点已于2026年11月由NexusPhp迁移至FluxTorrent架构，
 *   原 torrentrss.php / download.php 页面已不存在，改走兼容层接口
 */
class DriverHxpt extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'hxpt';

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
        if ('' === $this->getSubscriberToken()) {
            throw new InvalidArgumentException('hxpt 缺少FluxTorrent订阅令牌（fxr_ 前缀）');
        }
    }
}
