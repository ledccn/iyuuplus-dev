<?php

namespace process;

use app\admin\services\client\SeedingAfterCompletedServices;
use app\admin\services\client\TotalSeedingServices;
use app\admin\services\reseed\ReseedDownloadServices;
use app\admin\services\SitesServices;
use app\admin\services\SystemServices;
use app\admin\support\NotifyAdmin;
use app\model\Site;
use Error;
use Exception;
use plugin\cron\api\Install;
use support\Cache;
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

        // 修复：存在index.lock导致仓库更新失败的bug
        $indexLock = base_path() . '/.git/index.lock';
        if (current_git_commit() && is_file($indexLock) && is_readable($indexLock)) {
            unlink($indexLock);
        }

        // 必须安装后才能往下走
        if (!Install::isInstalled()) {
            return;
        }

        // 未加锁，导致服务器在2024年11月3日崩溃
        if (!Cache::has('ReseedProcess_onWorkerStart')) {
            Cache::set('ReseedProcess_onWorkerStart', time(), 3600 + mt_rand(100, 1000));
            SitesServices::sync();
        }

        // 数据库迁移不能太靠前执行
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

        // 自动更新 根据进程启动时间，设置一个随机10-20小时内的更新定时器
        Timer::add(mt_rand(36000, 72000), function () {
            SystemServices::checkRemoteUpdates();
        });

        // 每天执行
        new Crontab('10 10 * * *', function () {
            // 备份用户的各种配置
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
