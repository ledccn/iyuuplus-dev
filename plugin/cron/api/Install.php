<?php

namespace plugin\cron\api;

use InvalidArgumentException;
use plugin\admin\api\Menu;
use plugin\admin\app\common\Util;
use plugin\cron\app\admin\controller\CrontabController;
use RuntimeException;
use Throwable;

class Install
{
    /**
     * 安装
     * @return void
     */
    public static function install(): void
    {
        clearstatcache();
        // 生成配置文件
        $filename = dirname(__DIR__, 3) . '/config/crontab.php';
        if (!is_file($filename)) {
            $content = file_get_contents(dirname(__DIR__) . '/config/crontab.php');
            file_put_contents(
                $filename,
                str_replace('{{secret}}', \sha1(\microtime(true) . \uniqid('', true) . \mt_rand()), $content)
            );
        }

        if (Menu::get(CrontabController::class)) {
            return;
        }

        if (empty(Util::schema()->hasTable('cn_crontab'))) {
            self::importSqlFile(__DIR__ . '/install.sql');
        }

        // 导入菜单
        if ($menus = static::getMenus()) {
            Menu::import($menus);
        }
    }

    /**
     * 导入SQL文件
     * @param string $sqlFilePath
     * @return void
     */
    public static function importSqlFile(string $sqlFilePath): void
    {
        if (!file_exists($sqlFilePath)) {
            throw new InvalidArgumentException('sql文件不存在');
        }

        try {
            //读取.sql文件内容
            $sqlContent = file($sqlFilePath);

            $tmp = '';
            // 执行每个SQL语句
            foreach ($sqlContent as $line) {
                if (trim($line) == '' || stripos(trim($line), '--') === 0 || stripos(trim($line), '/*') === 0) {
                    continue;
                }

                $tmp .= $line;
                if (str_ends_with(trim($line), ';')) {
                    $tmp = str_ireplace('INSERT INTO', 'INSERT IGNORE INTO', $tmp);
                    Util::db()->statement($tmp);
                    $tmp = '';
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException('导入数据库失败：' . $e->getMessage());
        }
    }

    /**
     * 卸载
     *
     * @param $version
     * @return void
     */
    public static function uninstall($version): void
    {
        clearstatcache();
        // 移除配置文件
        $filename = dirname(__DIR__, 3) . '/config/crontab.php';
        if (is_file($filename)) {
            unlink($filename);
        }

        // 删除菜单
        foreach (static::getMenus() as $menu) {
            Menu::delete($menu['key']);
        }
    }

    /**
     * 更新
     *
     * @param $from_version
     * @param $to_version
     * @param $context
     * @return void
     */
    public static function update($from_version, $to_version, $context = null): void
    {
        // 删除不用的菜单
        if (isset($context['previous_menus'])) {
            static::removeUnnecessaryMenus($context['previous_menus']);
        }
        // 导入新菜单
        if ($menus = static::getMenus()) {
            Menu::import($menus);
        }
    }

    /**
     * 更新前数据收集等
     *
     * @param $from_version
     * @param $to_version
     * @return array|array[]
     */
    public static function beforeUpdate($from_version, $to_version): array
    {
        // 在更新之前获得老菜单，通过context传递给 update
        return ['previous_menus' => static::getMenus()];
    }

    /**
     * 获取菜单
     *
     * @return array|mixed
     */
    public static function getMenus(): mixed
    {
        clearstatcache();
        if (is_file($menu_file = dirname(__DIR__) . '/config/menu.php')) {
            $menus = include $menu_file;
            return $menus ?: [];
        }
        return [];
    }

    /**
     * 删除不需要的菜单
     *
     * @param $previous_menus
     * @return void
     */
    public static function removeUnnecessaryMenus($previous_menus): void
    {
        $menus_to_remove = array_diff(Menu::column($previous_menus, 'name'), Menu::column(static::getMenus(), 'name'));
        foreach ($menus_to_remove as $name) {
            Menu::delete($name);
        }
    }

}