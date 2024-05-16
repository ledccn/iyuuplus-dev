<?php

namespace app\admin\services\client;

use app\admin\support\NotifyAdmin;
use app\model\Client;
use Error;
use Exception;
use Iyuu\BittorrentClient\ClientEnums;
use support\Log;
use Throwable;

/**
 * 校验后做种
 */
class SeedingAfterCompletedServices
{
    /**
     * 执行
     * @return void
     */
    public static function run(): void
    {
        try {
            $list = Client::getEnabled()->get();
            $list->each(function (Client $client) {
                try {
                    match ($client->getClientEnums()) {
                        ClientEnums::qBittorrent => self::qBittorrent($client),
                        ClientEnums::transmission => self::transmission($client),
                        default => throw new \InvalidArgumentException('未知的下载器类型')
                    };
                } catch (Throwable $throwable) {
                    NotifyAdmin::warning('校验后做种 遍历异常：' . $throwable->getMessage());
                }
            });
        } catch (Error|Exception|Throwable $throwable) {
            Log::error('校验后做种 执行异常：' . $throwable->getMessage());
        } finally {
            clear_instance_cache();
        }
    }

    /**
     * @param Client $client
     * @return void
     */
    private static function qBittorrent(Client $client): void
    {
    }

    /**
     * @param Client $client
     * @return void
     */
    private static function transmission(Client $client): void
    {
    }
}
