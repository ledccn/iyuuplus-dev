<?php

namespace app\admin\services\client;

use app\admin\support\NotifyAdmin;
use app\model\Client;
use Error;
use Exception;
use support\Cache;
use support\Log;
use Throwable;

/**
 * 统计总做种量服务
 */
class TotalSeedingServices
{
    /**
     * 统计总做种数量
     * @return void
     */
    public static function run(): void
    {
        try {
            $total = 0;
            self::set(0);
            $list = Client::getEnabled()->get();
            $list->each(function (Client $client) use (&$total) {
                try {
                    $handler = ClientServices::createBittorrent($client);
                    $list = $handler->getTorrentList();
                    $hashDict = $list['hashString'];   // 哈希目录字典
                    $total += count($hashDict);
                } catch (Throwable $throwable) {
                    NotifyAdmin::warning('统计总做种数量 遍历异常：' . $throwable->getMessage());
                }
            });
            self::set($total);
        } catch (Error|Exception|Throwable $throwable) {
            Log::error('统计总做种数量 执行异常：' . $throwable->getMessage());
        } finally {
            clear_instance_cache();
        }
    }

    /**
     * 设置
     * @param int $total
     * @return void
     */
    private static function set(int $total): void
    {
        Cache::set(self::getCacheKey(), [$total, time()]);
    }

    /**
     * 获取
     * @return array
     */
    public static function get(): array
    {
        return Cache::get(self::getCacheKey(), [0, time()]);
    }

    /**
     * 缓存key
     * @return string
     */
    private static function getCacheKey(): string
    {
        return md5(iyuu_token() . __FILE__);
    }
}
