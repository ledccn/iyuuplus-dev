<?php

namespace app\admin\services\client;

use app\model\Client;
use InvalidArgumentException;
use Iyuu\BittorrentClient\Contracts\ConfigInterface;

/**
 * 获取下载器配置，编码、解码
 */
class Config implements ConfigInterface
{
    /**
     * 分隔符
     */
    public const SEPARATOR = ':';

    /**
     * 获取配置
     * @param string $name
     * @return array
     */
    public function get(string $name): array
    {
        [$uniqid, $brand] = $this->decode($name);
        if ($client = Client::find($uniqid)) {
            return $client->toArray();
        }

        throw new InvalidArgumentException('未找到下载器：' . $name);
    }

    /**
     * 编码
     * @param int $uniqid
     * @param string $brand
     * @return string
     */
    public function encode(int $uniqid, string $brand): string
    {
        return implode(self::SEPARATOR, [$uniqid, $brand]);
    }

    /**
     * 解码
     * @param string $name
     * @return array|string[]
     */
    public function decode(string $name): array
    {
        return explode(self::SEPARATOR, $name);
    }
}
