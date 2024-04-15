<?php

namespace plugin\cron\app;

use plugin\cron\app\model\Crontab;
use plugin\cron\app\model\CrontabLog;
use plugin\cron\app\model\CrontabLogObserver;
use plugin\cron\app\model\CrontabObserver;
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
        Crontab::observe(CrontabObserver::class);
        CrontabLog::observe(CrontabLogObserver::class);
    }
}
