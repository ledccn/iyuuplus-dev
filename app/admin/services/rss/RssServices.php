<?php

namespace app\admin\services\rss;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\LogicEnums;
use DOMDocument;
use DOMElement;
use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent;
use Iyuu\SiteManager\Utils;
use Ledc\Curl\Curl;
use plugin\cron\app\model\Crontab;
use RuntimeException;
use Throwable;

/**
 * RSS订阅服务
 */
class RssServices
{
    /**
     * 浏览器UA
     */
    protected const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36';
    /**
     * 计划任务：数据模型
     * @var Crontab
     */
    protected readonly Crontab $crontabModel;
    /**
     * 计划任务：调用参数
     * @var array
     */
    protected readonly array $parameter;
    /**
     * RSS地址
     * @var string
     */
    protected readonly string $rss_url;
    /**
     * 数据模型：下载器
     * @var Client
     */
    protected readonly Client $client;
    /**
     * 站点RSS框架，逻辑分支枚举
     * @var BranchEnums|null
     */
    protected ?BranchEnums $branchEnums;
    /**
     * 计划任务：标记规则
     * @var DownloaderMarkerEnums
     */
    protected readonly DownloaderMarkerEnums $downloaderMarkerEnums;
    /**
     * 保存路径
     * @var string
     */
    protected readonly string $save_path;
    /**
     * 种子大小逻辑
     * @var SizeLogic
     */
    protected readonly SizeLogic $sizeLogic;
    /**
     * 标题副标题匹配逻辑
     * @var MatchTitleLogic
     */
    protected readonly MatchTitleLogic $matchLogic;

    /**
     * 构造函数
     * @param int $crontab_id
     */
    public function __construct(public readonly int $crontab_id)
    {
        $crontabModel = Crontab::find($this->crontab_id);
        if (!$crontabModel) {
            throw new InvalidArgumentException('计划任务数据不存在');
        }
        $this->crontabModel = $crontabModel;

        $parameter = $crontabModel->parameter;
        if (is_string($parameter)) {
            $parameter = json_decode($parameter, true);
        }
        $this->parameter = $parameter;

        $this->rss_url = $parameter['rss_url'];
        $this->client = ClientServices::getClient($this->getParameter('client_id'));
        $this->downloaderMarkerEnums = DownloaderMarkerEnums::from($parameter['marker'] ?? DownloaderMarkerEnums::Empty->value);
        $this->save_path = $this->getParameter('save_path', '');
        $this->branchEnums = null;

        // 种子大小
        $this->sizeLogic = new SizeLogic(
            $this->getParameter('size_min', ''),
            $this->getParameter('size_min_unit', 'GB'),
            $this->getParameter('size_max', ''),
            $this->getParameter('size_max_unit', 'GB'),
        );

        $text_selector = $parameter['text_selector'] ?? '';
        $text_filter = $parameter['text_filter'] ?? '';
        $regex_selector = $parameter['regex_selector'] ?? '';
        $regex_filter = $parameter['regex_filter'] ?? '';

        // 解析：规则模式优先级，默认 简易模式 > 正则模式
        $ruleModeEnums = !empty($text_selector) || !empty($text_filter) ? RuleModeEnums::Simple : null;
        if (is_null($ruleModeEnums)) {
            $ruleModeEnums = !empty($regex_selector) || !empty($regex_filter) ? RuleModeEnums::Regex : null;
        }

        // 匹配规则设置
        $this->matchLogic = new MatchTitleLogic(
            $ruleModeEnums,
            explode(',', $text_selector),
            LogicEnums::create($parameter['text_selector_op'] ?? ''),
            explode(',', $text_filter),
            LogicEnums::create($parameter['text_filter_op'] ?? ''),
            $regex_selector,
            $regex_filter,
        );
    }

    /**
     * 执行
     * @return void
     */
    public function run(): void
    {
        $xml = $this->requestXML();
        $items = $this->parseXML($xml);

        $downloader = ClientServices::createBittorrent($this->client);
        foreach ($items as $item) {
            $this->sendToDownloader($downloader, $item);
        }
    }

    /**
     * 发送到下载器
     * @param Clients $downloader
     * @param TorrentItem $item
     * @return void
     */
    protected function sendToDownloader(Clients $downloader, TorrentItem $item): void
    {
        try {
            if (!$this->sizeLogic->match($item)) {
                echo '当前种子被大小过滤：' . $item->getTitle() . "【{$item->getSize()}】" . PHP_EOL;
                return;
            }

            if (!$this->matchLogic->match($item)) {
                echo '当前种子被规则过滤：' . $item->getTitle() . PHP_EOL;
                return;
            }

            echo '当前种子符合所有规则：' . $item->getTitle() . "【{$item->getSize()}】" . PHP_EOL;

            $torrent = new Torrent($item->getDownload(), false);
            $torrent->savePath = $this->save_path;
            $step = '1.调度事件，种子发送给下载器之前';
            $result = $downloader->addTorrent($torrent);
            $step = '2.调度事件，种子发送给下载器之后';

            echo '添加种子后，下载器响应：' . (is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE)) . PHP_EOL;
        } catch (Error|Exception|Throwable $e) {
            throw new RuntimeException('投递种子到下载器异常：' . $e->getMessage());
        }
    }

    /**
     * @return Curl
     */
    protected function createCurl(): Curl
    {
        $curl = new Curl();
        $curl->setUserAgent(static::USER_AGENT)
            ->setSslVerify()
            ->setTimeout(30, 30)
            ->setFollowLocation(1);

        return $curl;
    }

    /**
     * 请求XML
     * @return string
     */
    protected function requestXML(): string
    {
        $curl = $this->createCurl();
        $curl->get($this->rss_url);
        if (!$curl->isSuccess()) {
            $msg = $curl->error_message ?: 'error_message错误消息为空';
            throw new RuntimeException('下载XML失败：' . $msg);
        }

        $xml = $curl->response;
        if (is_bool($xml) || empty($xml)) {
            throw new RuntimeException('下载XML失败：curl_exec返回错误');
        }
        return $xml;
    }

    /**
     * 解析XML
     * @param string $xml
     * @return TorrentItem[]
     */
    protected function parseXML(string $xml): array
    {
        $items = [];
        try {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadXML($xml);
            libxml_clear_errors();
            $elements = $dom->getElementsByTagName('item');
            /** @var DOMElement $item */
            foreach ($elements as $item) {
                if (is_null($this->branchEnums)) {
                    $this->branchEnums = BranchEnums::create($this->rss_url, $dom, $item);
                }

                $torrent = new TorrentItem();
                $link = $item->getElementsByTagName('link')->item(0)->nodeValue;

                switch ($this->branchEnums) {
                    case BranchEnums::Unit3D:
                        $torrent_link = $link;
                        $length = null;
                        break;
                    default:
                        $torrent_link = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url');
                        $length = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('length');
                        break;
                }

                $guid = $item->getElementsByTagName('guid')?->item(0) !== null ? $item->getElementsByTagName('guid')->item(0)->nodeValue : md5($torrent_link);
                $pubDate = $item->getElementsByTagName('pubDate')?->item(0)?->nodeValue;

                $torrent->setTitle($item->getElementsByTagName('title')->item(0)->nodeValue)
                    ->setDownload($torrent_link)
                    ->setGuid($guid)
                    ->setTime($pubDate ? strtotime($pubDate) : time())
                    ->setLength($length)
                    ->setSize($length ? Utils::dataSize($length) : null);
                $items[] = $torrent;
            }
        } catch (Error|Exception|Throwable $e) {
            throw new RuntimeException('XML解析异常A：' . $e->getMessage());
        }

        if (empty($items)) {
            throw new RuntimeException('XML解析结果为空B：' . PHP_EOL . $xml);
        }

        return $items;
    }

    /**
     * 获取调用参数【支持 . 分割符】
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    private function getParameter(?string $key = null, mixed $default = null): mixed
    {
        if (null === $key) {
            return $this->parameter;
        }
        $keys = explode('.', $key);
        $value = $this->parameter;
        foreach ($keys as $index) {
            if (!isset($value[$index])) {
                return $default;
            }
            $value = $value[$index];
        }
        return $value;
    }
}
