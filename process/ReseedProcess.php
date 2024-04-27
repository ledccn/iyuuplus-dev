<?php

namespace process;

use app\admin\services\reseed\ReseedDownloadServices;
use app\admin\services\SitesServices;
use app\model\Site;
use Error;
use Exception;
use Iyuu\BittorrentClient\ClientDownloader;
use Iyuu\SiteManager\SiteManager;
use Ledc\Container\App;
use support\Log;
use Throwable;
use Workerman\Crontab\Crontab;
use Workerman\Timer;
use Workerman\Worker;

/**
 * IYUU运行必须的辅助进程
 */
class ReseedProcess
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        SitesServices::sync();
        init_migrate();
    }

    /**
     * 子进程启动时执行
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        clearstatcache();
        if (getenv('CONFIG_NOT_MYSQL') || !is_file(base_path('/.env'))
        ) {
            return;
        }

        // 每天执行
        new Crontab('10 10 * * *', function () {
            exec(implode(' ', [PHP_BINARY, base_path('webman'), 'iyuu:backup', 'backup']));
        });

        // 每60秒执行
        Timer::add(60, function () {
            try {
                $list = Site::getEnabled()->get();
                $list->each(function (Site $site) {
                    ReseedDownloadServices::handle($site);
                });
            } catch (Error|Exception|Throwable $throwable) {
                Log::error('ReseedProcess 进程异常：' . $throwable->getMessage());
            }

            // 清理缓存的驱动实例：防止变更配置后常驻内存未更新
            try {
                /** @var SiteManager $siteManager */
                $siteManager = App::pull(SiteManager::class);
                $siteManager->clearDriver();
                /** @var ClientDownloader $clientDownloader */
                $clientDownloader = App::pull(ClientDownloader::class);
                $clientDownloader->clearDriver();
            } catch (Error|Exception|Throwable $throwable) {
                Log::error('ReseedProcess 进程清理缓存驱动实例异常：' . $throwable->getMessage());
            }
        });
    }

    /**
     * 子进程停止时执行
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStop(Worker $worker): void
    {
    }
}
