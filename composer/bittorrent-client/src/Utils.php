<?php

namespace Iyuu\BittorrentClient;

/**
 * 工具类
 */
class Utils
{
    /**
     * 判断传入的种子参数是否为URL下载链接
     * @param string $torrent 种子URL或种子元数据
     * @return bool
     */
    public static function isTorrentUrl(string $torrent): bool
    {
        return match (true) {
            str_starts_with($torrent, 'http://'), str_starts_with($torrent, 'https://'), str_starts_with($torrent, 'magnet:?xt=urn:btih:') => true,
            default => false,
        };
    }

    /**
     * 对布尔型进行格式化
     * @param mixed $value 变量值
     * @return boolean 格式化后的变量
     */
    public static function booleanParse(mixed $value): bool
    {
        return match (true) {
            is_bool($value) => $value,
            is_numeric($value) => $value > 0,
            is_string($value) => in_array(strtolower($value), ['ok', 'true', 'success', 'on', 'yes', '(ok)', '(true)', '(success)', '(on)', '(yes)']),
            is_array($value) => !empty($value),
            default => (bool)$value,
        };
    }
}
