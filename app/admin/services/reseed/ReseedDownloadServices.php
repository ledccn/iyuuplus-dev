<?php

namespace app\admin\services\reseed;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\enums\ReseedStatusEnums;
use app\model\Reseed;
use app\model\Site;
use Error;
use Exception;
use Iyuu\BittorrentClient\ClientEnums;
use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent as TorrentContract;
use Iyuu\SiteManager\Config;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Spider\Helper;
use support\Log;
use Throwable;
use Webman\Event\Event;

/**
 * 自动辅种下载种子服务类
 */
class ReseedDownloadServices
{
    /**
     * 处理自动辅种表，下载种子塞给下载器
     * @param Site $site
     * @return void
     */
    public static function handle(Site $site): void
    {
        if (Reseed::getStatusEqDefault($site->sid)->doesntExist()) {
            // 无数据，返回
            return;
        }

        $config = new Config($site->toArray());
        $limit = $config->getLimit();
        if (empty($limit)) {
            // 不限速的站点
            self::handleOpen($site);
        } else {
            // 限速的站点
            $limitCount = $limit['count'] ?? 20;
            $limitSleep = $limit['sleep'] ?? 10;
            if (empty($limitCount)) {
                self::handleOpen($site, $limitSleep);
            } else {
                self::handleLimited($site, $limitCount, $limitSleep);
            }
        }
    }

    /**
     * 开放的站点
     * @param Site $site
     * @param int $limitSleep
     * @return void
     */
    private static function handleOpen(Site $site, int $limitSleep = 0): void
    {
        Reseed::getStatusEqDefault($site->sid)->chunkById(100, function ($records) use ($limitSleep) {
            /** @var Reseed $reseed */
            foreach ($records as $reseed) {
                // 更新：调度时间
                $reseed->dispatch_time = time();
                if (false === self::sendDownloader($reseed, $limitSleep)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 限速的站点
     * @param Site $site 站点数据模型
     * @param int $limitCount 每天限制辅种总数量
     * @param int $limitSleep 每个种子间隔时间，单位:秒
     * @return void
     */
    private static function handleLimited(Site $site, int $limitCount, int $limitSleep): void
    {
        // 24小时内辅种数
        $total24h = Reseed::where('sid', '=', $site->id)
            ->where('dispatch_time', '>', time() - 86400)
            ->whereIn('status', [ReseedStatusEnums::Success->value, ReseedStatusEnums::Fail->value])
            ->count();

        if ($total24h < $limitCount) {
            Reseed::getStatusEqDefault($site->sid)->chunkById($limitCount - $total24h, function ($records) use ($limitSleep) {
                /** @var Reseed $reseed */
                foreach ($records as $reseed) {
                    // 更新：调度时间
                    $reseed->dispatch_time = time();
                    if (false === self::sendDownloader($reseed, $limitSleep)) {
                        return false;
                    }
                }
                return false;
            });
        }
    }

    /**
     * 发送到下载器
     * @param Reseed $reseed 自动辅种数据模型
     * @param int $limitSleep 每个种子间隔时间，单位:秒
     * @return bool
     */
    public static function sendDownloader(Reseed $reseed, int $limitSleep = 0): bool
    {
        try {
            $torrent = new Torrent([
                'site' => $reseed->site,
                'id' => $reseed->reseed_id,
                'sid' => $reseed->sid,
                'torrent_id' => $reseed->torrent_id,
                'group_id' => $reseed->group_id,
            ]);
            $response = Helper::download($torrent);
            // 调度事件：下载种子之后
            Event::dispatch('reseed.torrent.download.after', [$response, $reseed]);

            $clientModel = ClientServices::getClient($reseed->client_id);
            $bittorrentClients = ClientServices::createBittorrent($clientModel);
            $contractsTorrent = new TorrentContract($response->payload, $response->metadata);
            $contractsTorrent->savePath = $reseed->directory;

            // 调度事件：把种子发送给下载器之前
            self::sendBefore($contractsTorrent, $bittorrentClients, $clientModel);
            Event::dispatch('reseed.torrent.send.before', [$contractsTorrent, $bittorrentClients, $reseed]);

            $result = $bittorrentClients->addTorrent($contractsTorrent);

            // 调度事件：把种子发送给下载器之后
            self::sendAfter($result, $bittorrentClients, $clientModel, $reseed);
            Event::dispatch('reseed.torrent.send.after', [$result, $bittorrentClients, $clientModel, $reseed]);

            // 更新模型数据
            $reseed->message = is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE);
            $reseed->status = ReseedStatusEnums::Success->value;
            $reseed->save();

            return true;
        } catch (Error|Exception|Throwable $throwable) {
            $reseed->message = $throwable->getMessage();
            $reseed->status = ReseedStatusEnums::Fail->value;
            $reseed->save();
        } finally {
            if (0 < $limitSleep) {
                sleep(min($limitSleep, 60));
            }
        }

        return true;
    }

    /**
     * 把种子发送给下载器前，做一些操作
     * @param TorrentContract $contractsTorrent
     * @param Clients $bittorrentClients
     * @param Client $clientModel
     * @return void
     */
    private static function sendBefore(TorrentContract $contractsTorrent, Clients $bittorrentClients, Client $clientModel): void
    {
        switch ($clientModel->getClientEnums()) {
            case ClientEnums::transmission:
                $contractsTorrent->parameters['paused'] = true;     // 添加任务校验后是否暂停
                break;
            case ClientEnums::qBittorrent;
                $contractsTorrent->parameters['autoTMM'] = 'false'; // 关闭自动种子管理
                $contractsTorrent->parameters['paused'] = 'true';   // 添加任务校验后是否暂停
                $contractsTorrent->parameters['category'] = 'IYUU自动辅种';   // 添加分类标签
                $contractsTorrent->parameters['root_folder'] = $clientModel->root_folder ? 'true' : 'false';    // 是否创建根目录
                break;
        }
    }

    /**
     * 把种子发送给下载器之后，做一些操作
     * @param mixed $result
     * @param Clients $bittorrentClients
     * @param Client $clientModel
     * @param Reseed $reseed
     * @return void
     */
    private static function sendAfter(mixed $result, Clients $bittorrentClients, Client $clientModel, Reseed $reseed): void
    {
        try {
            switch ($clientModel->getClientEnums()) {
                case ClientEnums::qBittorrent:
                    if (is_string($result) && str_contains(strtolower($result), 'ok')) {
                        // 发送校验命令
                        /** @var \Iyuu\BittorrentClient\Driver\qBittorrent\Client $bittorrentClients */
                        $bittorrentClients->recheck($reseed->info_hash);
                    }
                    break;
                default:
                    break;
            }
        } catch (Throwable $throwable) {
            Log::error('把种子发送给下载器之后，做一些操作，异常啦：' . $throwable->getMessage());
        }
    }
}
