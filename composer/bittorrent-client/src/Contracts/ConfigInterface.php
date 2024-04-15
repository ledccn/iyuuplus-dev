<?php

namespace Iyuu\BittorrentClient\Contracts;

/**
 * 配置接口
 */
interface ConfigInterface
{
    /**
     * 获取配置
     * @param string $name 下载器标识
     * @return array
     */
    public function get(string $name): array;

    /**
     * 编码标识
     * @param int $uniqid 配置项ID
     * @param string $brand 驱动品牌类型
     * @return string
     */
    public function encode(int $uniqid, string $brand): string;

    /**
     * 解码标识
     * - 解码后格式：[$uniqid, $driver]
     * @param string $name 下载器标识
     * @return array<int, string>
     */
    public function decode(string $name): array;
}
