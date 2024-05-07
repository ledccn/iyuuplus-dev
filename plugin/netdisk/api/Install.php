<?php

namespace plugin\netdisk\api;

use plugin\admin\api\Menu;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use support\Db;
use support\exception\BusinessException;
use Throwable;

class Install
{
    /**
     * 安装
     *
     * @param $version
     * @return void
     */
    public static function install($version)
    {
        // 安装数据库
        static::importDb();
        // 导入菜单
        if($menus = static::getMenus()) {
            Menu::import($menus);
        }
    }

    /**
     * 卸载
     *
     * @param $version
     * @return void
     * @throws BusinessException
     */
    public static function uninstall($version)
    {
        // 禁止卸载基础插件 2024年5月7日
        throw new BusinessException('禁止卸载基础插件');

        // 删除菜单
        foreach (static::getMenus() as $menu) {
            Menu::delete($menu['key']);
        }
        // 删除表格
        static::dropTables();
    }

    /**
     * 更新
     *
     * @param $from_version
     * @param $to_version
     * @param $context
     * @return void
     */
    public static function update($from_version, $to_version, $context = null)
    {
        // 删除不用的菜单
        if (isset($context['previous_menus'])) {
            static::removeUnnecessaryMenus($context['previous_menus']);
        }

        // 安装数据库
        static::importDb();

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
    public static function beforeUpdate($from_version, $to_version)
    {
        // 在更新之前获得老菜单，通过context传递给 update
        return ['previous_menus' => static::getMenus()];
    }

    /**
     * 导入数据库
     *
     * @return void
     */
    public static function importDb()
    {
        // 安装文件默认放置于应用插件根目录下
        $mysqlDumpFile = __DIR__ . '/../install.sql';
        if (!is_file($mysqlDumpFile)) {
            return;
        }
        foreach (explode(';', file_get_contents($mysqlDumpFile)) as $sql) {
            if ($sql = trim($sql)) {
                try {
                    Db::connection('plugin.admin.mysql')->statement($sql);
                } catch (Throwable $e) {}
            }
        }
    }

    /**
     * 导入数据库
     *
     * @return void
     */
    protected static function dropTables()
    {
        $tables = ['io_share', 'io_source'];
        foreach ($tables as $table) {
            try {
                Db::schema('plugin.admin.mysql')->drop($table);
            } catch (Throwable $e) {}
        }
    }

    /**
     * 获取菜单
     *
     * @return array|mixed
     */
    public static function getMenus()
    {
        clearstatcache();
        if (is_file($menu_file = __DIR__ . '/../config/menu.php')) {
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
    public static function removeUnnecessaryMenus($previous_menus)
    {
        $menus_to_remove = array_diff(Menu::column($previous_menus, 'name'), Menu::column(static::getMenus(), 'name'));
        foreach ($menus_to_remove as $name) {
            Menu::delete($name);
        }
    }

}