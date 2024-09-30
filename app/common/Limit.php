<?php

namespace app\common;

use RuntimeException;
use support\exception\BusinessException;

/**
 * 频率限制接口
 */
class Limit
{
    /**
     * 按分钟限制频率
     * @param $key
     * @param $maxRequests
     * @return void
     * @throws BusinessException
     */
    public static function perMinute($key, $maxRequests): void
    {
        $prefix = "minute-$key-";
        $name = date('YmdHi') . ".limit";
        static::by($prefix, $name, $maxRequests);
    }

    /**
     * 按天限制频率
     * @param $key
     * @param $maxRequests
     * @return void
     * @throws BusinessException
     */
    public static function perDay($key, $maxRequests): void
    {
        $prefix = "day-$key-";
        $name = date('Ymd') . ".limit";
        static::by($prefix, $name, $maxRequests);
    }

    /**
     * 通用频率限制
     * @param $prefix
     * @param $name
     * @param $maxRequests
     * @return void
     * @throws BusinessException
     */
    public static function by($prefix, $name, $maxRequests): void
    {
        $date = date('Ymd');
        $basePath = "tmp/limit/$date";
        if (!is_dir(runtime_path($basePath))) {
            foreach (glob(runtime_path("tmp/limit/*")) as $dir) {
                echo $dir;
                remove_dir($dir);
            }
        }
        $file = runtime_path("$basePath/{$prefix}$name");
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!is_file($file)) {
            if (!preg_match('/^[0-9a-zA-Z\-_.:\[\]]+$/', $prefix)) {
                throw new RuntimeException('$prefix只能是字母和数字以及(-_.)的组合');
            }
            foreach (glob(runtime_path("$basePath/$prefix*")) as $expiredFile) {
                unlink($expiredFile);
            }
            file_put_contents($file, 1);
            return;
        }
        $count = (int)file_get_contents($file);
        if ($count++ >= $maxRequests) {
            throw new BusinessException('请求速度过快，请稍后访问');
        }
        file_put_contents($file, $count);
    }
}
