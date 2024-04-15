<?php
namespace Webman\Push;

class Install
{
    const WEBMAN_PLUGIN = true;

    /**
     * @var array
     */
    protected static $pathRelation = [
        'config/plugin/webman/push' => 'config/plugin/webman/push'
    ];

    /**
     * Install
     * @return void
     */
    public static function install()
    {
        $config_app_path = __DIR__ . '/config/plugin/webman/push/app.php';
        $config_app_content = file_get_contents($config_app_path);
        $app_key = md5(microtime(true).rand(0, 2100000000));
        $app_secret = md5($app_key.rand(0, 2100000000));
        $config_app_content = str_replace([
            'APP_KEY_TO_REPLACE',
            'APP_SECRET_TO_REPLACE'
        ], [$app_key, $app_secret], $config_app_content);
        file_put_contents($config_app_path, $config_app_content);
        static::installByRelation();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
    }

    /**
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            if ($pos = strrpos($dest, '/')) {
                $parent_dir = base_path().'/'.substr($dest, 0, $pos);
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }
            //symlink(__DIR__ . "/$source", base_path()."/$dest");
            copy_dir(__DIR__ . "/$source", base_path()."/$dest");
        }
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            $path = base_path()."/$dest";
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }
            /*if (is_link($path) {
                unlink($path);
            }*/
            remove_dir($path);
        }
    }
}
