<?php

namespace app\admin\services\download;

use app\model\Site;
use Iyuu\SiteManager\Contracts\Config as ContractsConfig;
use Iyuu\SiteManager\Contracts\ConfigInterface;
use Iyuu\SiteManager\Contracts\Response;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Spider\Helper;
use Ledc\Container\App;
use RuntimeException;

/**
 * 下载服务
 */
class DownloaderServices
{
    /**
     * 进程启动时onWorkerStart时运行的回调配置
     * @return void
     */
    public static function bootstrap(): void
    {
        $config = getenv('CONFIG_NOT_MYSQL') ? new ContractsConfig() : new Config();
        App::getInstance()->instance(ConfigInterface::class, $config);
    }

    /**
     * 下载种子二进制或生成下载种子的链接
     * @param array $data
     * @param bool $metadata
     * @return Response
     * @throws TorrentException
     */
    public function download(array $data, bool $metadata = true): Response
    {
        $torrent = $this->convert($data);
        return Helper::download($torrent, $metadata);
    }

    /**
     * 转换数组为种子对象
     * @param array $data
     * @return Torrent
     */
    public function convert(array $data): Torrent
    {
        $sid = $data['sid'] ?? 0;
        if ($siteModel = Site::uniqueSid($sid)) {
            $data['site'] = $siteModel->site;
            return new Torrent($data);
        }

        throw new RuntimeException('不支持的站点：' . $sid);
    }
}
