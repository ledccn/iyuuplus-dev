<?php

namespace app\admin\services\client;

use app\admin\support\NotifyAdmin;
use app\model\Client;
use app\model\Reseed;
use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\BittorrentClient\ClientEnums;
use Iyuu\BittorrentClient\Exception\NotFoundException;
use Iyuu\BittorrentClient\Exception\ServerErrorException;
use Iyuu\BittorrentClient\Exception\UnauthorizedException;
use support\Log;
use Throwable;

/**
 * 校验后做种
 * - 检测校验完成的种子，并对100%完成率的种子恢复做种
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
            $list = Client::getEnabledSeedingAfterCompleted()->get();
            $list->each(function (Client $client) {
                try {
                    match ($client->getClientEnums()) {
                        ClientEnums::qBittorrent => self::qBittorrent($client),
                        ClientEnums::transmission => self::transmission($client),
                        default => throw new InvalidArgumentException('未知的下载器类型')
                    };
                } catch (Throwable $throwable) {
                    $msg = '校验后做种 遍历异常：' . $throwable->getMessage();
                    Log::error($msg);
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
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    private static function qBittorrent(Client $client): void
    {
        /** @var \Iyuu\BittorrentClient\Driver\qBittorrent\Client $handler */
        $handler = ClientServices::createBittorrent($client);
        $list = $handler->getList(['filter' => 'paused']);
        //file_put_contents(runtime_path('qb.txt'), var_export($list, true));
        $infohash_list = [];
        foreach ($list as $i => $item) {
            $infohash = $item['hash'] ?? '';
            $completed = $item['completed'] ?? null;
            $state = $item['state'] ?? null;
            $size = $item['size'] ?? null;
            $total_size = $item['total_size'] ?? null;
            if (null === $completed || null === $state || null === $size || null === $total_size) {
                throw new InvalidArgumentException('qBittorrent下载器返回的种子信息，缺少必要字段，无法判断种子状态');
            }
            if (empty($infohash)) {
                throw new InvalidArgumentException('qBittorrent下载器返回的种子信息，缺少hash字段');
            }
            // 挑选条件
            if (\Iyuu\BittorrentClient\Driver\qBittorrent\Client::STATE_pausedUP === $state && ($completed === $total_size || $completed === $size)) {
                $infohash_list[] = $infohash;
            }
        }

        if (empty($infohash_list)) {
            return;
        }

        $db_infohash_list = self::getDbInfoHashList($client, $infohash_list);
        $intersect = array_intersect($infohash_list, $db_infohash_list);
        if (!empty($intersect)) {
            $msg = sprintf('下载器ID：%d | 下载器名称：%s | 校验完成且暂停种子：%d个，符合自动做种条件，已发送做种指令', $client->id, $client->title, count($intersect));
            Log::info($msg);
            $handler->resume($intersect);
        }

        $diff = array_diff($infohash_list, $db_infohash_list);
        if (!empty($diff)) {
            $msg = sprintf('下载器ID：%d | 下载器名称：%s | 校验完成且暂停种子：%d个，符合自动做种条件，他们未在自动辅种表', $client->id, $client->title, count($diff));
            Log::info($msg);
            //$handler->resume($diff);
            echo $msg . PHP_EOL;
        }
    }

    /**
     * @param Client $client
     * @return void
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws UnauthorizedException
     */
    private static function transmission(Client $client): void
    {
        /** @var \Iyuu\BittorrentClient\Driver\transmission\Client $handler */
        $handler = ClientServices::createBittorrent($client);
        $list = $handler->getList();
        //file_put_contents(runtime_path('tr.txt'), var_export($list, true));
        $infohash_list = $ids = [];
        foreach ($list as $item) {
            $id = $item['id'] ?? null;
            $infohash = $item['hashString'] ?? '';
            $status = $item['status'] ?? null;
            $leftUntilDone = $item['leftUntilDone'] ?? null;
            $totalSize = $item['totalSize'] ?? null;
            if (null === $status || null === $leftUntilDone || null === $totalSize) {
                throw new InvalidArgumentException('transmission下载器返回的种子信息，缺少必要字段，无法判断种子状态');
            }
            if (empty($infohash)) {
                throw new InvalidArgumentException('transmission下载器返回的种子信息，缺少hashString字段');
            }
            if (empty($id)) {
                throw new InvalidArgumentException('transmission下载器返回的种子信息，缺少id字段');
            }
            // 挑选条件
            if (\Iyuu\BittorrentClient\Driver\transmission\Client::TR_STATUS_STOPPED === $status && 0 === $leftUntilDone) {
                $infohash_list[] = $infohash;
                $ids[$infohash] = $id;
            }
        }

        if (empty($infohash_list)) {
            return;
        }

        $db_infohash_list = self::getDbInfoHashList($client, $infohash_list);
        $intersect = array_intersect($infohash_list, $db_infohash_list);
        if (!empty($intersect)) {
            $msg = sprintf('下载器ID：%d | 下载器名称：%s | 校验完成且暂停种子：%d个，符合自动做种条件，已发送做种指令', $client->id, $client->title, count($intersect));
            Log::info($msg);
            $intersect_ids = array_filter($ids, function ($k) use ($intersect) {
                return in_array($k, $intersect);
            }, ARRAY_FILTER_USE_KEY);
            $handler->start(array_values($intersect_ids));
        }

        $diff = array_diff($infohash_list, $db_infohash_list);
        if (!empty($diff)) {
            $msg = sprintf('下载器ID：%d | 下载器名称：%s | 校验完成且暂停种子：%d个，符合自动做种条件，他们未在自动辅种表', $client->id, $client->title, count($diff));
            Log::info($msg);
            $diff_ids = array_filter($ids, function ($k) use ($diff) {
                return in_array($k, $diff);
            }, ARRAY_FILTER_USE_KEY);
            //$handler->start(array_values($diff_ids));
            echo $msg . PHP_EOL;
        }
    }

    /**
     * 从数据库获取info_hash数组
     * @param Client $client
     * @param array $infohash_list
     * @return array
     */
    private static function getDbInfoHashList(Client $client, array $infohash_list): array
    {
        $total = count($infohash_list);
        $builder = Reseed::getSuccessByClientIdInfoHash($client->id, $infohash_list);
        if (0 === $builder->count()) {
            throw new InvalidArgumentException("当前校验完成暂停的种子：{$total}个，他们未在自动辅种表");
        }

        return $builder->pluck('info_hash')->toArray();
    }
}
