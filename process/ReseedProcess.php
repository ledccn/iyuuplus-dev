<?php

namespace process;

use app\admin\services\client\SeedingAfterCompletedServices;
use app\admin\services\client\TotalSeedingServices;
use app\admin\services\reseed\ReseedDownloadServices;
use app\admin\services\SitesServices;
use app\admin\support\NotifyAdmin;
use app\model\Site;
use Error;
use Exception;
use plugin\cron\api\Install;
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
     * 子进程启动时执行
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        clearstatcache();
        if (!Install::isInstalled()) {
            return;
        }

        SitesServices::sync();
        init_migrate();

        // docker s6环境
        if (is_docker_exists_nginx()) {
            // nginx：切割访问log，保留30天
            new Crontab('0 0 * * *', function () {
                clearstatcache();
                if (!is_file('/var/log/nginx/access.log')) {
                    return;
                }
                $previousDate = date('Y-m-d', strtotime('-1 day'));
                $previous7DaysDate = date('Y-m-d', strtotime('-7 day'));
                $accessLogFileName = "/var/log/nginx/access.$previousDate.log";
                $errorLogFileName = "/var/log/nginx/error.$previousDate.log";
                $access7DaysLogFileName = "/var/log/nginx/access.$previous7DaysDate.log";
                $error7DaysLogFileName = "/var/log/nginx/error.$previous7DaysDate.log";
                $commands = [
                    "mv /var/log/nginx/access.log $accessLogFileName",
                    "mv /var/log/nginx/error.log $errorLogFileName",
                    "kill -USR1 $(pidof nginx)",
                    "gzip $access7DaysLogFileName",
                    "gzip $error7DaysLogFileName",
                    "rm -f $access7DaysLogFileName",
                    "rm -f $error7DaysLogFileName",
                    "find /var/log/nginx/ -name 'access.*.log.gz' -type f -mtime +30 -delete",
                    "find /var/log/nginx/ -name 'error.*.log.gz' -type f -mtime +30 -delete"
                ];
                foreach ($commands as $command) {
                    exec($command);
                }
            });
        }

        // 每天执行
        new Crontab('10 10 * * *', function () {
            exec(implode(' ', [PHP_BINARY, base_path('webman'), 'iyuu:backup', 'backup']));
        });

        // 每4小时执行
        new Crontab('0 */4 * * *', function () {
            SeedingAfterCompletedServices::run();
        });

        // 每3600秒执行
        TotalSeedingServices::run();
        Timer::add(3600, function () {
            TotalSeedingServices::run();
        });

        // 每60秒执行
        Timer::add(60, function () {
            try {
                $list = Site::getEnabled()->get();
                $list->each(function (Site $site) {
                    try {
                        ReseedDownloadServices::handle($site);
                    } catch (Throwable $throwable) {
                        NotifyAdmin::warning('ReseedProcess 进程异常：' . $throwable->getMessage() . ' 站点：' . $site->nickname);
                    }
                });
            } catch (Error|Exception|Throwable $throwable) {
                Log::error('ReseedProcess 进程异常：' . $throwable->getMessage());
            } finally {
                clear_instance_cache();
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
