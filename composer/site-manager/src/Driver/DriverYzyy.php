<?php

namespace Iyuu\SiteManager\Driver;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;

/**
 * yzyy（YzYY 站：Discuz + dz_seed 插件）
 * - 种子下载：download.php?info_hash={info_hash}&passkey={passkey}（只认 passkey，不需要cookie）
 * - 列表爬取：torrents.php?page={page}（需要登录cookie）
 */
class DriverYzyy extends BaseDriver implements Processor
{
    /**
     * 站点名称
     */
    public const SITE_NAME = 'yzyy';
}
