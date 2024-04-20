<?php

namespace app\install;

use app\admin\services\SitesServices;
use app\model\Site;
use plugin\admin\api\Menu;
use plugin\admin\app\common\Util;
use plugin\cron\api\Install as CrontabInstall;
use plugin\email\api\Install as EmailInstall;
use plugin\sms\api\Install as SmsInstall;

/**
 * 安装类
 */
class Installation
{
    /**
     * 菜单KEY
     */
    const MENU_KEY = 'management_center';

    /**
     * 安装方法
     * @param string $version
     * @return void
     */
    public static function install(string $version = ''): void
    {
        $first = false;
        try {
            Util::pauseFileMonitor();
            // 安装应用插件和数据库
            if (empty(Util::schema()->hasTable('cn_client'))) {
                CrontabInstall::importSqlFile(__DIR__ . '/iyuuplus.sql');
                CrontabInstall::install();
                EmailInstall::install();
                SmsInstall::install();
                $first = true;
            }

            // 安装菜单
            if (!Menu::get(self::MENU_KEY)) {
                Menu::import(include __DIR__ . '/menu.php');
            }
        } catch (\Error|\Exception|\Throwable $throwable) {
            echo $throwable->getMessage() . PHP_EOL;
        } finally {
            Util::resumeFileMonitor();
        }

        if ($first) {
            safe_webman_stop();
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
     * 初始化数据
     * @return void
     */
    public static function initDatabase(): void
    {
        if (Util::schema()->hasTable('cn_sites') && !Site::exists()) {
            SitesServices::sync();
        }
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
