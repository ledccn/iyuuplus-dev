<?php

namespace app\admin\services\client;

use app\model\Client;
use Iyuu\BittorrentClient\ClientDownloader;
use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\ConfigInterface;
use Iyuu\BittorrentClient\Contracts\Torrent;
use Iyuu\SiteManager\Contracts\Response;
use Ledc\Container\App;
use RuntimeException;

/**
 * 下载器服务
 */
class ClientServices
{
    /**
     * 进程启动时onWorkerStart时运行的回调配置
     * @return void
     */
    public static function bootstrap(): void
    {
        App::getInstance()->instance(ConfigInterface::class, new Config());
    }

    /**
     * 获取默认客户端下载器数据模型
     * @return Client
     */
    public static function getDefaultClient(): Client
    {
        if ($model = Client::getDefaultClient()) {
            return $model;
        }
        throw new RuntimeException('未设置默认客户端');
    }

    /**
     * 获取客户端模型
     * @param int $id
     * @return Client
     */
    public static function getClient(int $id): Client
    {
        $client = Client::find($id);
        if ($client instanceof Client) {
            return $client;
        }
        throw new RuntimeException('客户端不存在：' . $id);
    }

    /**
     * 凭主键，创建下载器的实例
     * @param int $id 配置ID
     * @return Clients
     */
    public static function createBittorrentById(int $id): Clients
    {
        $client = Client::find($id);
        if ($client instanceof Client) {
            return static::createBittorrent($client);
        }
        throw new RuntimeException('客户端不存在：' . $id);
    }

    /**
     * 凭模型对象，创建下载器的实例
     * @param Client $client
     * @return Clients
     */
    public static function createBittorrent(Client $client): Clients
    {
        /** @var ClientDownloader $clientDownloader */
        $clientDownloader = App::pull(ClientDownloader::class);
        $name = $clientDownloader->config->encode($client->id, $client->brand);
        return $clientDownloader->select($name);
    }

    /**
     * 立刻下载，发送种子对象到下载器
     * @param Response $response
     * @param Client $client 客户端模型对象
     * @return mixed
     */
    public static function sendClientDownloader(Response $response, Client $client): mixed
    {
        $bittorrentClients = static::createBittorrent($client);
        $torrent = new Torrent($response->payload, $response->metadata);
        return $bittorrentClients->addTorrent($torrent);
    }
}
