<?php

namespace Iyuu\SiteManager;

use RuntimeException;

/**
 * 工具类
 */
class Utils
{
    /**
     * 判断windows操作系统
     * @return bool
     */
    public static function isWindowsOs(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * 从文本内移除正则匹配到的内容
     * @param string $pattern 正则表达式
     * @param string $subject 待处理的文本
     * @return string
     */
    public static function regexRemove(string $pattern, string $subject): string
    {
        if (preg_match($pattern, $subject, $matches)) {
            return str_replace($matches[0], '', $subject);
        }
        return $subject;
    }

    /**
     * 移除URL的协议和主机，返回URI（路径和查询字符串）
     * @param string $url
     * @return string
     */
    public static function removeSchemeHost(string $url): string
    {
        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            $info = parse_url($url);
            $url = str_replace($info['scheme'] . '://' . $info['host'], '', $url);
        }
        return $url;
    }

    /**
     * 转换成易读的容量格式(包含小数)
     * @param float|int $bytes 字节
     * @param string $delimiter 分隔符 [&nbsp; | <br />]
     * @param int $decimals 保留小数点
     * @return string
     */
    public static function dataSize(float|int $bytes, string $delimiter = '', int $decimals = 2): string
    {
        $type = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $i = 0;
        while ($bytes >= 1024) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, $decimals) . $delimiter . $type[$i];
    }

    /**
     * 创建目录
     * @param string $directory
     * @return void
     */
    public static function createDir(string $directory): void
    {
        clearstatcache();
        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create the "%s" directory', $directory));
            }
        }
        if (!is_writable($directory)) {
            throw new RuntimeException(sprintf('Unable to write in the "%s" directory', $directory));
        }
    }

    /**
     * 显示
     * @param mixed $data
     * @return void
     */
    public static function echo(mixed $data): void
    {
        $str = PHP_EOL . '******************************' . date('Y-m-d H:i:s') . PHP_EOL;
        $content = match (true) {
            is_bool($data) => $data ? 'true' : 'false',
            is_null($data) => 'null',
            default => print_r($data, true)
        };
        $str .= $content . PHP_EOL;
        $str .= '**********' . PHP_EOL;
        echo $str;
    }
}
