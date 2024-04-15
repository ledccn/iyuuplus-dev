<?php

namespace Iyuu\SiteManager\Contracts;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Spider\SpiderTorrents;

/**
 * 观察者接口
 */
interface Observer
{
    /**
     * 契约方法
     * @param SpiderTorrents $spiderTorrents
     * @param BaseDriver $baseDriver
     * @param int $index
     * @return void
     */
    public static function update(SpiderTorrents $spiderTorrents, BaseDriver $baseDriver, int $index): void;
}
