<?php

namespace app\install;

use plugin\admin\api\Menu;
use plugin\admin\app\common\Util;
use plugin\cron\api\Install as CrontabInstall;
use plugin\email\api\Install as EmailInstall;
use plugin\netdisk\api\Install as NetDiskInstall;
use plugin\sms\api\Install as SmsInstall;

/**
 * 安装类
 */
class Installation
{
    /**
     * 菜单KEY
     */
    const string MENU_KEY = 'management_center';

    /**
     * 安装方法
     * @param string $version
     * @return void
     */
    public static function install(string $version = ''): void
    {
        try {
            Util::pauseFileMonitor();
            // 安装应用插件
            CrontabInstall::install();
            EmailInstall::install();
            SmsInstall::install();
            if (empty(Util::schema()->hasTable('io_source'))) {
                NetDiskInstall::install(iyuu_version());
            }

            // 安装数据库
            if (empty(Util::schema()->hasTable('cn_client'))) {
                CrontabInstall::importSqlFile(__DIR__ . '/iyuuplus.sql');
            }

            // 安装菜单
            if (!Menu::get(self::MENU_KEY)) {
                Menu::import(include __DIR__ . '/menu.php');
            }
            //安装动态令牌菜单
            if (!Menu::get('app\admin\controller\TotpController::class')) {
                Menu::import(include __DIR__ . '/menu.php');
            }
        } catch (\Error|\Exception|\Throwable $throwable) {
            echo '【中间件安装必须插件】异常：'. $throwable->getMessage() . PHP_EOL;
        } finally {
            Util::resumeFileMonitor();
        }
    }

    /**
     * 初始化Env文件
     * @param array $params
     * @return void
     */
    public static function initEnvFile(array $params): void
    {
        $_env = file_get_contents(base_path('/.env.example'));
        $env = strtr($_env, $params);
        file_put_contents(base_path('/.env'), $env);
    }

    /**
     * 初始化配置文件
     * @return void
     */
    public static function initConfig(): void
    {
        // webman/push
        // google2fa
    }

    /**
     * 卸载方法
     * @param string $version
     * @return void
     */
    public static function uninstall(string $version = ''): void
    {
    }
}
