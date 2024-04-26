<?php

namespace app\admin\services\reseed;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\ReseedStatusEnums;
use app\model\enums\ReseedSubtypeEnums;
use app\model\payload\ReseedPayload;
use app\model\Reseed;
use app\model\Site;
use InvalidArgumentException;
use Iyuu\BittorrentClient\Clients;
use Ledc\Curl\Curl;
use plugin\cron\app\model\Crontab;
use RuntimeException;

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
     * 计划任务：标记规则
     * @var DownloaderMarkerEnums
     */
    protected DownloaderMarkerEnums $downloaderMarkerEnums;
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
        [$this->crontabModel, $this->crontabSites, $this->crontabClients, $this->downloaderMarkerEnums] = $this->parseCrontab($crontab_id);
        $this->notifyData = new NotifyData(Site::count(), count($this->crontabSites));
    }

    /**
     * 执行辅种逻辑
     * @return void
     */
    public function run(): void
    {
        // 第一层循环：辅种下载器
        foreach ($this->crontabClients as $client_id => $on) {
            $this->clientModel = ClientServices::getClient((int)$client_id);
            $this->bittorrentClient = ClientServices::createBittorrent($this->clientModel);

            echo "正在从 {$this->clientModel->title} 下载器获取当前做种hash..." . PHP_EOL;

            $torrentList = $this->bittorrentClient->getTorrentList();
            $hashDict = $torrentList['hashString'];   // 哈希目录字典
            $total = count($hashDict);
            $this->notifyData->hashCount += $total;
            if (self::RESEED_GROUP_NUMBER < $total) {
                // 分批次辅种
                $full = json_decode($torrentList['hash'], true);
                $chunkHash = array_chunk($full, self::RESEED_GROUP_NUMBER);
                foreach ($chunkHash as $info_hash) {
                    sort($info_hash);
                    $hash = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
                    $data = [
                        'hash' => $hash,
                        'sha1' => sha1($hash),
                        'sign' => $this->token,
                        'timestamp' => time(),
                        'version' => iyuu_version(),
                    ];
                    $this->requestApi($hashDict, $data);
                }
            } else {
                // all in one
                $data = [
                    'hash' => $torrentList['hash'],
                    'sha1' => $torrentList['sha1'],
                    'sign' => $this->token,
                    'timestamp' => time(),
                    'version' => iyuu_version(),
                ];
                $this->requestApi($hashDict, $data);
            }
        }
    }

    /**
     * 请求服务器API，得到可辅种数据
     * @param array $hashDict 当前客户端infohash与目录对应的字典
     * @param array $data
     * @return void
     */
    protected function requestApi(array $hashDict, array $data): void
    {
        static $curl;
        if (!$curl) {
            $curl = new Curl();
            $curl->setHeader('token', $this->token)->setSslVerify();
        }
        $curl->post('http://api.bolahg.cn/App.Api.Infohash', $data);
        if (!$curl->isSuccess()) {
            throw new RuntimeException('请求辅种信息失败，服务器无响应' . ($curl->error_message ?? ''), 500);
        }

        $response = json_decode($curl->response, true);
        $result = $response['data'] ?? [];
        $msg = $response['msg'] ?? '远端服务器无响应，请稍后重试！';
        $code = $response['ret'] ?? false;
        if (200 !== $code) {
            echo '-----辅种失败，原因：' . $msg . PHP_EOL . PHP_EOL;
            return;
        }
        if (empty($result)) {
            echo '-----没有查询到可辅种数据' . PHP_EOL . PHP_EOL;
            return;
        }

        $this->currentReseed($hashDict, $result);
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
            // 第三层循环：单种子infohash可辅种数据
            foreach ($reseed['torrent'] as $id => $value) {
                $this->notifyData->reseedCount++;
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

                $attributes = [
                    'client_id' => $this->clientModel->id,
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
     * @return array
     */
    private function parseCrontab(int $crontab_id): array
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
        $marker = DownloaderMarkerEnums::from($parameter['marker'] ?? DownloaderMarkerEnums::Empty->value);
        return [$crontabModel, $sites, $clients, $marker];
    }
}
