<?php

namespace app;

use app\admin\services\client\ClientServices;
use app\admin\services\download\DownloaderServices;
use app\admin\services\reseed\CrontabObserver;
use app\admin\services\reseed\ReseedTemplate;
use app\admin\services\transfer\CrontabObserver as TransferCrontabObserver;
use app\admin\services\transfer\TransferTemplate;
use app\model\Client;
use app\model\ClientObserver;
use app\model\Folder;
use app\model\FolderObserver;
use app\model\Reseed;
use app\model\ReseedObserver;
use app\model\Site;
use app\model\SiteObserver;
use app\model\Totp;
use app\model\TotpObserver;
use app\model\Transfer;
use app\model\TransferObserver;
use plugin\cron\api\CrontabExtend;
use plugin\cron\app\model\Crontab;
use Workerman\Worker;

/**
 * 进程启动时onWorkerStart时运行的回调配置
 * @link https://learnku.com/articles/6657/model-events-and-observer-in-laravel
 */
class Bootstrap implements \Webman\Bootstrap
{
    /**
     * @param Worker|null $worker
     * @return void
     */
    public static function start(?Worker $worker): void
    {
        self::initObserver();
        self::initCrontabExtend();

        ClientServices::bootstrap();
        DownloaderServices::bootstrap();
    }

    /**
     * 初始化模型观察者
     * @return void
     */
    protected static function initObserver(): void
    {
        //【新增】依次触发的顺序是：
        //saving -> creating -> created -> saved

        //【更新】依次触发的顺序是:
        //saving -> updating -> updated -> saved

        // updating 和 updated 会在数据库中的真值修改前后触发。
        // saving 和 saved 则会在 Eloquent 实例的 original 数组真值更改前后触发
        Client::observe(ClientObserver::class);
        Crontab::observe(CrontabObserver::class);
        Crontab::observe(TransferCrontabObserver::class);
        Folder::observe(FolderObserver::class);
        Reseed::observe(ReseedObserver::class);
        Site::observe(SiteObserver::class);
        Totp::observe(TotpObserver::class);
        Transfer::observe(TransferObserver::class);
    }

    /**
     * 扩展支持新的计划任务类型
     * @return void
     */
    protected static function initCrontabExtend(): void
    {
        CrontabExtend::getInstance()
            ->register(new ReseedTemplate())
            ->register(new TransferTemplate());
    }
}
