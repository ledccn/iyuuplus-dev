<?php

namespace app\admin\services\reseed;

use app\admin\services\client\ClientServices;
use app\admin\support\NotifyAdmin;
use app\admin\support\NotifyHelper;
use app\model\Client;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\NotifyChannelEnums;
use app\model\enums\ReseedStatusEnums;
use app\model\enums\ReseedSubtypeEnums;
use app\model\Folder;
use app\model\payload\ReseedPayload;
use app\model\Reseed;
use app\model\Site;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Iyuu\BittorrentClient\Clients;
use Iyuu\ReseedClient\InternalServerErrorException;
use plugin\cron\app\model\Crontab;
use support\Log;
use Throwable;
use Webman\Event\Event;

/**
 * 自动辅种服务
 */
class ReseedServices
{
    /**
     * 辅种每批次分组数量
     */
    private const int RESEED_GROUP_NUMBER = 500;
    /**
     * 计划任务：数据模型
     * @var Crontab
     */
    protected Crontab $crontabModel;
    /**
     * 计划任务：辅种站点（已选择的）
     * @var array
     */
    protected array $crontabSites;
    /**
     * 计划任务：辅种下载器（已选择的）
     * @var array
     */
    protected array $crontabClients;
    /**
     * 计划任务：主辅种下载器的数据模型
     * @var Client|null
     */
    protected ?Client $masterModel = null;
    /**
     * 计划任务：主辅种下载器的实例
     * @var Clients|null
     */
    protected ?Clients $masterBittorrentClient = null;
    /**
     * 路径过滤器
     * @var array
     */
    protected array $path_filter = [];
    /**
     * 计划任务：通知渠道
     * @var NotifyChannelEnums|null
     */
    protected ?NotifyChannelEnums $notifyEnum;
    /**
     * 计划任务：标记规则
     * @var DownloaderMarkerEnums
     */
    protected DownloaderMarkerEnums $downloaderMarkerEnums;
    /**
     * 计划任务：自动校验
     * @var string
     */
    protected string $auto_check = '';
    /**
     * 辅种完毕后的通知数据
     * @var NotifyData
     */
    protected NotifyData $notifyData;
    /**
     * 当前数据模型
     * @var Client
     */
    protected Client $clientModel;
    /**
     * 当前下载器实例
     * @var Clients
     */
    protected Clients $bittorrentClient;
    /**
     * 缓存站点模型
     * @var array<int, Site>
     */
    protected array $cacheSiteModel = [];

    /**
     * 构造函数
     * @param string $token IYUU的token
     */
    public function __construct(public readonly string $token, public readonly int $crontab_id)
    {
        if (empty($this->token)) {
            throw new InvalidArgumentException('缺少IYUU_TOKEN');
        }
        check_iyuu_token($this->token);
        $this->parseCrontab($crontab_id);
        $this->notifyData = new NotifyData(Site::count(), count($this->crontabSites));
    }

    /**
     * 执行辅种逻辑
     * @return void
     * @throws InternalServerErrorException
     */
    public function run(): void
    {
        $reseedClient = new \Iyuu\ReseedClient\Client(iyuu_token());
        $sid_sha1 = $this->getSidSha1($reseedClient);

        // 第一层循环：辅种下载器
        foreach ($this->crontabClients as $client_id => $on) {
            $this->clientModel = ClientServices::getClient((int)$client_id);
            $this->bittorrentClient = ClientServices::createBittorrent($this->clientModel);

            echo "正在从 {$this->clientModel->title} 下载器获取当前做种hash..." . PHP_EOL;

            try {
                $torrentList = $this->bittorrentClient->getTorrentList();
            } catch (Throwable $throwable) {
                echo '从下载器获取做种哈希失败：' . $throwable->getMessage() . PHP_EOL;
                continue;
            }

            $hashDict = $torrentList['hashString'];   // 哈希目录字典
            $total = count($hashDict);
            echo "{$this->clientModel->title} 下载器获取到做种哈希总数：{$total}" . PHP_EOL;

            $torrentList = $this->pathFilter($torrentList);
            if (empty($torrentList)) {
                echo "{$this->clientModel->title} 下载器排除目录后，做种哈希为空" . PHP_EOL;
                continue;
            } else {
                $hashDict = $torrentList['hashString'];   // 哈希目录字典
                $total = count($hashDict);
                echo "{$this->clientModel->title} 下载器排除目录后，剩余做种哈希总数：{$total}" . PHP_EOL;
            }

            // 调度事件：当前客户端辅种开始前
            Event::emit('reseed.current.before', [$hashDict, $this->bittorrentClient, $this->clientModel]);

            $this->notifyData->hashCount += $total;
            if (self::RESEED_GROUP_NUMBER < $total) {
                // 分批次辅种
                $full = json_decode($torrentList['hash'], true);
                $chunkHash = array_chunk($full, self::RESEED_GROUP_NUMBER);
                foreach ($chunkHash as $info_hash) {
                    sort($info_hash);
                    $hash = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
                    try {
                        echo "正在请求IYUU服务器，匹配辅种{$this->clientModel->title} " . PHP_EOL;
                        $result = $reseedClient->reseed($hash, sha1($hash), $sid_sha1, iyuu_version());
                        $this->currentReseed($hashDict, $result);
                    } catch (InternalServerErrorException $throwable) {
                        echo "匹配辅种异常：InternalServerErrorException" . PHP_EOL;
                        throw $throwable;
                    } catch (Throwable $throwable) {
                        echo "匹配辅种异常：Throwable" . PHP_EOL;
                        echo $throwable->getMessage() . PHP_EOL;
                    }
                }
            } else {
                // all in one
                try {
                    $result = $reseedClient->reseed($torrentList['hash'], $torrentList['sha1'], $sid_sha1, iyuu_version());
                    $this->currentReseed($hashDict, $result);
                } catch (InternalServerErrorException $throwable) {
                    throw $throwable;
                } catch (Throwable $throwable) {
                    echo $throwable->getMessage() . PHP_EOL;
                }
            }

            // 调度事件：当前客户端辅种结束后
            Event::emit('reseed.current.after', [$hashDict, $this->bittorrentClient, $this->clientModel]);
        }

        // 调度事件：全部客户端辅种结束
        Event::emit('reseed.all.done', [$this->notifyData, $this->crontabModel, $this->crontabClients]);

        try {
            $this->sendNotify();
        } catch (Throwable $throwable) {
            Log::error('辅种后发送通知时异常：' . $throwable->getMessage());
            NotifyAdmin::error($throwable->getMessage());
        }
    }

    /**
     * 路径过滤器
     * @param array $hashArray
     * @return array|null
     */
    protected function pathFilter(array $hashArray): ?array
    {
        if (empty($this->path_filter)) {
            return $hashArray;
        }

        $hashDict = $hashArray['hashString'];   // 哈希目录字典
        $rs = [];
        foreach ($hashDict as $key => $value) {
            $filter = false;
            foreach ($this->path_filter as $prefix) {
                if (str_starts_with(rtrim($value, DIRECTORY_SEPARATOR), rtrim($prefix, DIRECTORY_SEPARATOR))) {
                    $filter = true;
                }
            }
            if (false === $filter) {
                $rs[$key] = $value;
            }
        }

        if (empty($rs)) {
            return null;
        }

        $info_hash = array_keys($rs);
        sort($info_hash);
        $json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
        $hashArray['hash'] = $json;
        $hashArray['sha1'] = sha1($json);
        $hashArray['hashString'] = $rs;
        return $hashArray;
    }

    /**
     * 发送通知
     * @return void
     * @throws GuzzleException
     */
    protected function sendNotify(): void
    {
        $br = PHP_EOL;
        $text = 'IYUU自动辅种-统计报表';
        $desp = '### 版本号：' . iyuu_version() . $br;
        $desp .= '**支持站点：' . $this->notifyData->supportSitesCount . '**  [当前支持自动辅种的站点数量]' . $br;
        $desp .= '**辅种站点：' . $this->notifyData->userSitesCount . '**  [勾选辅种的站点数量]' . $br;
        $desp .= '**总做种：' . $this->notifyData->hashCount . '**  [客户端做种的hash总数]' . $br;
        $desp .= '**返回数据：' . $this->notifyData->reseedCount . '**  [服务器返回的可辅种数据]' . $br;
        $desp .= '**重复：' . $this->notifyData->reseedRepeat . '**  [客户端已做种]' . $br;
        $desp .= '**跳过：' . $this->notifyData->reseedSkip . '**  [未设置passkey]' . $br;

        $response = match ($this->notifyEnum) {
            NotifyChannelEnums::notify_iyuu => NotifyHelper::iyuu($text, $desp),
            NotifyChannelEnums::notify_server_chan => NotifyHelper::serverChan($text, $desp),
            NotifyChannelEnums::notify_bark => NotifyHelper::bark($text, $desp),
            NotifyChannelEnums::notify_qy_weixin => NotifyHelper::weWork($text . $br . $desp),
            NotifyChannelEnums::notify_webhook => NotifyHelper::webhook($text, $desp),
            default => null
        };
        if ($response) {
            Log::info('辅种结束发送通知后的响应：' . $response->getBody());
        }
    }

    /**
     * 获取已开启站点哈希值
     * @param \Iyuu\ReseedClient\Client $reseedClient
     * @return string
     * @throws InternalServerErrorException
     */
    protected function getSidSha1(\Iyuu\ReseedClient\Client $reseedClient): string
    {
        $sites = array_keys($this->crontabSites);
        $sid_list = Site::getEnabled()->whereIn('site', $sites)->pluck('sid')->toArray();
        return $reseedClient->reportExisting($sid_list);
    }

    /**
     * 辅种当前客户端
     * @param array $hashDict 当前客户端infohash与目录对应的字典
     * @param array $result 服务器接口返回的可辅种结果
     * @return void
     */
    protected function currentReseed(array $hashDict, array $result): void
    {
        // 第二层循环：接口返回的可辅种结果
        foreach ($result as $infohash => $reseed) {
            $downloadDir = $hashDict[$infohash];   // 辅种目录
            $dirReseedCount = count($reseed['torrent']);
            $this->notifyData->reseedCount += $dirReseedCount;
            $_reseedCount = str_pad((string)$dirReseedCount, 5);
            echo "种子哈希：{$infohash} 可辅种数：{$_reseedCount} 做种目录：{$downloadDir}" . PHP_EOL;
            // 第三层循环：单种子infohash可辅种数据
            foreach ($reseed['torrent'] as $id => $value) {
                $sid = $value['sid'];   // 站点id
                $torrent_id = $value['torrent_id'];  // 种子id
                $reseed_infohash = $value['info_hash'];  // 种子infohash

                $siteModel = $this->getSiteModel($sid);
                if (!$siteModel) {
                    $this->notifyData->reseedSkip++;
                    echo "站点sid {$sid}  | 不存在 | 忽略辅种：" . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    continue;
                }

                $site = $siteModel->site;

                // 判断是否禁用
                if ($siteModel->disabled) {
                    $this->notifyData->reseedSkip++;
                    echo "站点 {$site}  | 已禁用 | 忽略辅种：" . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    continue;
                }
                // 判断是否选择辅种站点
                if (!$this->isSelectSite($site)) {
                    $this->notifyData->reseedSkip++;
                    echo "站点 {$site}  | 未勾选辅种站点 | 忽略辅种：" . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    continue;
                }
                // 跳过已有的种子
                if (isset($hashDict[$reseed_infohash])) {
                    $this->notifyData->reseedRepeat++;
                    echo "站点 {$site}  | 下载器存在种子 | 忽略辅种：" . json_encode($value, JSON_UNESCAPED_UNICODE) . PHP_EOL;
                    continue;
                }

                // 有效载荷
                $reseedPayload = new ReseedPayload();
                $reseedPayload->marker = $this->downloaderMarkerEnums->value;
                $reseedPayload->auto_check = $this->auto_check;

                // 主辅分离
                if ($this->masterModel) {
                    echo "【主辅分离】主辅种客户端 {$this->masterModel->title}，辅种将添加到此下载器" . PHP_EOL;
                }
                $attributes = [
                    'client_id' => $this->masterModel ? $this->masterModel->id : $this->clientModel->id,
                    'info_hash' => $reseed_infohash,
                ];
                $values = [
                    'site' => $site,
                    'sid' => $sid,
                    'torrent_id' => $torrent_id,
                    'group_id' => $value['group'] ?? 0,
                    'directory' => $downloadDir,
                    'dispatch_time' => 0,
                    'status' => ReseedStatusEnums::Default->value,
                    'subtype' => ReseedSubtypeEnums::Default->value,
                    'payload' => (string)$reseedPayload
                ];
                Reseed::firstOrCreate($attributes, $values);
                // 统计成功数
                $this->notifyData->reseedSuccess++;
                $this->notifyData->incrReseedSuccessData($site);
            }
        }
    }

    /**
     * 获取站点模型
     * @param int $sid
     * @return Site|null
     */
    protected function getSiteModel(int $sid): ?Site
    {
        if (isset($this->cacheSiteModel[$sid])) {
            return $this->cacheSiteModel[$sid];
        }

        if ($siteModel = Site::uniqueSid($sid)) {
            $this->cacheSiteModel[$sid] = $siteModel;
        }
        return $siteModel;
    }

    /**
     * 判断是否选择辅种站点
     * @param string $site
     * @return bool
     */
    protected function isSelectSite(string $site): bool
    {
        return isset($this->crontabSites[$site]);
    }

    /**
     * 解析任务
     * @param int $crontab_id
     */
    private function parseCrontab(int $crontab_id): void
    {
        $crontabModel = Crontab::find($crontab_id);
        if (!$crontabModel) {
            throw new InvalidArgumentException('计划任务数据不存在');
        }

        $parameter = $crontabModel->parameter;
        if (is_string($parameter)) {
            $parameter = json_decode($parameter, true);
        }
        $sites = $parameter['sites'];
        $clients = $parameter['clients'];
        // 路径过滤器
        if ($path_filter = $parameter['path_filter'] ?? []) {
            $this->path_filter = Folder::whereIn('folder_id', explode(',', $path_filter))->pluck('folder_value')->toArray();
        }

        $notify_channel = $parameter['notify_channel'] ?? '';
        $marker = DownloaderMarkerEnums::from($parameter['marker'] ?? DownloaderMarkerEnums::Empty->value);
        $auto_check = $parameter['auto_check'] ?? '';

        // 主辅种下载的主键id
        $master = $parameter['master'] ?? null;
        if ($master) {
            $this->masterModel = ClientServices::getClient((int)$master);
            $this->masterBittorrentClient = ClientServices::createBittorrent($this->masterModel);
        }

        $this->crontabModel = $crontabModel;
        $this->crontabSites = $sites;
        $this->crontabClients = $clients;
        $this->notifyEnum = NotifyChannelEnums::tryFrom($notify_channel);
        $this->downloaderMarkerEnums = $marker;
        $this->auto_check = $auto_check;
    }
}
