<?php

namespace process;

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
            //更新配置文件
            $systemNginxConfigPath = '/etc/nginx/nginx.conf';
            $dockerNginxConfigPath = '/iyuu/docker/files/etc/nginx/nginx.conf';
            if (md5_file($systemNginxConfigPath) !== md5_file($dockerNginxConfigPath)) {
                if (copy($dockerNginxConfigPath, $systemNginxConfigPath)) {
                    exec('nginx -s reload');
                }
            }
            // nginx：切割访问log，保留30天
            new Crontab('0 0 * * *', function () {
                clearstatcache();
                if (!is_file('/var/log/nginx/access.log')) {
                    return;
                }
                $previousDate = date('Y-m-d', strtotime('-1 day'));
                $previous7DaysDate  = date('Y-m-d', strtotime('-7 day'));
                $accessLogFileName = "/var/log/nginx/access.$previousDate.log";
                $errorLogFileName = "/var/log/nginx/error.$previousDate.log";
                exec("mv /var/log/nginx/access.log $accessLogFileName");
                exec("mv /var/log/nginx/error.log $errorLogFileName");
                exec('kill -USR1 $(pidof nginx)');
                exec("gzip $previous7DaysDate");
                exec("gzip $previous7DaysDate");
                exec("find /var/log/nginx/ -name 'access.*.log.gz' -type f -mtime +30 -delete");
                exec("find /var/log/nginx/ -name 'error.*.log.gz' -type f -mtime +30 -delete");
            });
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
