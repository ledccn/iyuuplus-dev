<?php

namespace Iyuu\SiteManager;

use Iyuu\SiteManager\Contracts\Config;
use Iyuu\SiteManager\Contracts\ConfigInterface;
use Iyuu\SiteManager\Observers\ReportObserver;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Ledc\Container\App;
use Workerman\Worker;

/**
 * 进程启动前初始化
 */
class Bootstrap implements \Webman\Bootstrap
{
    /**
     * onWorkerStart
     * @param Worker|null $worker
     * @return void
     */
    public static function start(?Worker $worker): void
    {
        SpiderTorrents::observer(ReportObserver::class);

        // 创建备份目录
        if (!is_dir(dirname(Config::getFilename()))) {
            Utils::createDir(dirname(Config::getFilename()));
        }

        // 绑定依赖到容器
        if (!App::getInstance()->bound(ConfigInterface::class)) {
            App::getInstance()->instance(ConfigInterface::class, new Config());
        }
    }
}
