<?php

namespace Iyuu\SiteManager\Spider;

use Iyuu\SiteManager\BaseDriver;

/**
 * 有效载荷
 */
readonly class Payload
{
    /**
     * 构造函数
     * @param SpiderTorrents $spiderTorrents 爬虫使用的种子对象
     * @param BaseDriver $baseDriver 站点基础类
     */
    public function __construct(public SpiderTorrents $spiderTorrents, public BaseDriver $baseDriver)
    {
    }
}
