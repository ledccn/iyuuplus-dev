<?php

namespace app\admin\services\transfer;

use app\admin\services\client\ClientServices;
use app\enums\EventTransferEnums;
use app\model\Client;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\ReseedSubtypeEnums;
use app\model\Folder;
use app\model\IyuuDocuments;
use app\model\Transfer;
use InvalidArgumentException;
use Iyuu\BittorrentClient\ClientEnums;
use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent as TorrentContract;
use Iyuu\BittorrentClient\Exception\ServerErrorException;
use Iyuu\BittorrentClient\Utils;
use plugin\cron\app\model\Crontab;
use Rhilip\Bencode\Bencode;
use Rhilip\Bencode\ParseException;
use support\Log;
use Throwable;
use Webman\Event\Event;

/**
 * 自动转移做种客户端服务类
 */
class TransferServices
{
    /**
     * 路径分隔符
     */
    public const string Delimiter = '{#**#}';
    /**
     * 计划任务：数据模型
     * @var Crontab
     */
    protected Crontab $crontabModel;
    /**
     * 计划任务：调用参数
     * @var array
     */
    protected array $parameter;
    /**
     * 数据模型：来源下载器
     * @var Client
     */
    protected Client $from_clients;
    /**
     * 数据模型：目标下载器
     * @var Client
     */
    protected Client $to_client;
    /**
     * 计划任务：标记规则
     * @var DownloaderMarkerEnums
     */
    protected DownloaderMarkerEnums $downloaderMarkerEnums;
    /**
     * 路径过滤器
     * - 优先级：高
     * @var array
     */
    protected array $path_filter = [];
    /**
     * 路径选择器
     * - 优先级：低
     * @var array
     */
    protected array $path_selector = [];
    /**
     * 路径转换类型
     * @var PathConvertTypeEnums
     */
    protected PathConvertTypeEnums $path_convert_type = PathConvertTypeEnums::Eq;
    /**
     * 路径转换规则
     * @var array
     */
    protected array $path_convert_rule = [];
    /**
     * 跳校验
     * @var bool
     */
    protected bool $skip_check = false;
    /**
     * 暂停
     * @var bool
     */
    protected bool $paused = false;
    /**
     * 删除源做种
     * @var bool
     */
    protected bool $delete_torrent = false;

    /**
     * 构造函数
     * @param int $crontab_id
     */
    public function __construct(public readonly int $crontab_id)
    {
        [$this->crontabModel, $this->parameter, $this->downloaderMarkerEnums] = $this->parseCrontab();
        $this->parseOthers();
    }

    /**
     * 执行逻辑
     * @return void
     * @throws ServerErrorException
     */
    public function run(): void
    {
        // 来源
        $fromBittorrentClient = ClientServices::createBittorrent($this->from_clients);
        // 目标
        $toBittorrentClient = ClientServices::createBittorrent($this->to_client);

        // 来源下载器，qb版本大于4.4
        $qBittorrent_version_geq_4_4 = $this->versionGEQ44($fromBittorrentClient);

        echo "正在从 {$this->from_clients->title} 下载器获取当前做种hash..." . PHP_EOL;

        $torrentList = $fromBittorrentClient->getTorrentList();
        $hashDict = $torrentList['hashString'];   // 哈希目录字典
        $move = $torrentList[Clients::TORRENT_LIST];

        // 调度事件：转移前
        Event::dispatch(EventTransferEnums::transfer_action_before->value, [$hashDict, $fromBittorrentClient, $toBittorrentClient]);

        // 第一层循环：哈希目录字典
        foreach ($hashDict as $infohash => $downloadDirOriginal) {
            $attributes = [
                'from_client_id' => $this->from_clients->id,
                'to_client_id' => $this->to_client->id,
                'info_hash' => $infohash,
            ];

            if (Transfer::where($attributes)->exists()) {
                echo '存在缓存（管理中心 - 自动转移），当前种子哈希：' . $infohash . ' 已忽略。' . PHP_EOL;
                continue;
            }

            if ($this->pathFilter($downloadDirOriginal)) {
                continue;
            }

            echo PHP_EOL;
            // 做种实际路径与相对路径之间互转
            echo '转换前：' . $downloadDirOriginal . PHP_EOL;
            $downloadDir = $this->pathConvert($downloadDirOriginal);
            echo '转换后：' . $downloadDir . PHP_EOL;

            if (empty($downloadDir)) {
                $msg = '路径转换参数配置错误，请重新配置！';
                echo $msg . PHP_EOL;
                Transfer::updateOrCreate($attributes, [
                    'directory' => $downloadDirOriginal,
                    'convert_directory' => '',
                    'message' => $msg,
                    'state' => 0,
                    'last_time' => time(),
                ]);
                return;
            }

            $rocket = new TransferRocket($infohash, $this->from_clients->torrent_path, $move);
            try {
                $contractsTorrent = match ($this->from_clients->getClientEnums()) {
                    ClientEnums::transmission => $this->handleTransmission($rocket),
                    ClientEnums::qBittorrent => $this->handleQBittorrent($rocket, $qBittorrent_version_geq_4_4),
                    default => throw new InvalidArgumentException('未匹配到下载器类型'),
                };
            } catch (\Throwable $throwable) {
                echo '【读取种子元信息】异常：' . $throwable->getMessage() . PHP_EOL;
                Transfer::updateOrCreate($attributes, [
                    'directory' => $downloadDirOriginal,
                    'convert_directory' => $downloadDir,
                    'message' => $throwable->getMessage(),
                    'state' => 0,
                    'last_time' => time(),
                ]);
                continue;
            }

            echo '存在种子：' . $rocket->torrentFile . PHP_EOL;

            $contractsTorrent->savePath = $downloadDir;

            // 调度事件：把种子发送给下载器之前
            $this->sendBefore($contractsTorrent, $fromBittorrentClient, $toBittorrentClient, $rocket);

            echo "将把种子文件推送给下载器，正在转移做种客户端..." . PHP_EOL . PHP_EOL;
            $ret = $toBittorrentClient->addTorrent($contractsTorrent);
            echo '成功推送种子到下载器...' . PHP_EOL;

            // 调度事件：把种子发送给下载器之后
            $this->sendAfter($contractsTorrent, $fromBittorrentClient, $toBittorrentClient, $rocket, $ret);

            if ($ret) {
                $state = 1;
                //转移成功时，删除做种，不删资源
                if ($this->delete_torrent) {
                    $fromBittorrentClient->delete($rocket->torrentDelete);
                }
            } else {
                // 失败的种子
                $state = 0;
            }

            Transfer::updateOrCreate($attributes, [
                'directory' => $downloadDirOriginal,
                'convert_directory' => $downloadDir,
                'torrent_file' => $rocket->torrentFile,
                'message' => is_string($ret) ? $ret : json_encode($ret, JSON_UNESCAPED_UNICODE),
                'state' => $state,
                'last_time' => time(),
            ]);
        }
    }

    /**
     * 把种子发送给下载器前，做一些操作
     * @param TorrentContract $contractsTorrent
     * @param Clients $fromBittorrentClient
     * @param Clients $toBittorrentClient
     * @param TransferRocket $rocket
     * @return void
     */
    private function sendBefore(TorrentContract $contractsTorrent, Clients $fromBittorrentClient, Clients $toBittorrentClient, TransferRocket $rocket): void
    {
        try {
            switch ($this->to_client->getClientEnums()) {
                case ClientEnums::qBittorrent:
                    if (DownloaderMarkerEnums::Category === $this->downloaderMarkerEnums) {
                        // 添加分类
                        $contractsTorrent->parameters['category'] = 'IYUU' . ReseedSubtypeEnums::text(ReseedSubtypeEnums::Transfer);
                    }
                    break;
                case ClientEnums::transmission:
                    if (DownloaderMarkerEnums::Empty !== $this->downloaderMarkerEnums) {
                        // 添加标签 （tr只有标签）
                        $contractsTorrent->parameters['labels'] = ['IYUU' . ReseedSubtypeEnums::text(ReseedSubtypeEnums::Transfer)];
                    }
                    break;
                default:
                    echo '把种子发送给下载器前，未匹配到操作' . PHP_EOL;
                    break;
            }
        } catch (Throwable $throwable) {
            Log::error('把种子发送给下载器之前，做一些操作，异常啦：' . $throwable->getMessage());
        }
    }

    /**
     * 把种子发送给下载器之后，做一些操作
     * @param TorrentContract $contractsTorrent
     * @param Clients $fromBittorrentClient
     * @param Clients $toBittorrentClient
     * @param TransferRocket $rocket
     * @param mixed $result
     * @return void
     */
    private function sendAfter(TorrentContract $contractsTorrent, Clients $fromBittorrentClient, Clients $toBittorrentClient, TransferRocket $rocket, mixed $result): void
    {
        try {
            switch ($this->to_client->getClientEnums()) {
                case ClientEnums::qBittorrent:
                    if (is_string($result) && str_contains(strtolower($result), 'ok')) {
                        /** @var \Iyuu\BittorrentClient\Driver\qBittorrent\Client $toBittorrentClient */
                        // 标记标签 2024年4月25日
                        if (DownloaderMarkerEnums::Tag === $this->downloaderMarkerEnums) {
                            $toBittorrentClient->torrentAddTags($rocket->infohash, 'IYUU' . ReseedSubtypeEnums::text(ReseedSubtypeEnums::Transfer));
                        }
                    }
                    break;
                default:
                    break;
            }
        } catch (Throwable $throwable) {
            Log::error('把种子发送给下载器之后，做一些操作，异常啦：' . $throwable->getMessage());
        }
    }

    /**
     * 读取种子元数据
     * @param TransferRocket $rocket
     * @return TorrentContract
     */
    private function handleTransmission(TransferRocket $rocket): TorrentContract
    {
        $infohash = $rocket->infohash;
        $path = $rocket->path;
        $move = $rocket->move;

        $extra_options = [];
        $extra_options['paused'] = $this->paused;

        // 优先使用API提供的种子路径
        $torrentFile = $move[$infohash]['torrentFile'];
        $rocket->torrentDelete = $move[$infohash]['id'];
        // API提供的种子路径不存在时，使用配置内指定的BT_backup路径
        if (!is_file($torrentFile)) {
            $torrentFile = str_replace("\\", "/", $torrentFile);
            $torrentFile = $path . strrchr($torrentFile, '/');
        }
        // 再次检查
        if (!is_file($torrentFile)) {
            echo implode(PHP_EOL, IyuuDocuments::get('transfer.help', [])) . PHP_EOL;
            throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentFile}`不存在，无法完成转移！");
        }

        $rocket->torrentFile = $torrentFile;

        //读取种子源文件
        $metadata = file_get_contents($torrentFile);
        $contractsTorrent = new TorrentContract($metadata, true);
        $contractsTorrent->parameters = $extra_options;

        return $contractsTorrent;
    }

    /**
     * 读取种子元数据
     * @param TransferRocket $rocket
     * @param bool $needPatchTorrent
     * @return TorrentContract
     */
    private function handleQBittorrent(TransferRocket $rocket, bool $needPatchTorrent): TorrentContract
    {
        $infohash = $rocket->infohash;
        $path = $rocket->path;
        $move = $rocket->move;
        $help_msg = implode(PHP_EOL, IyuuDocuments::get('transfer.help', [])) . PHP_EOL;

        $extra_options = [];
        $extra_options['autoTMM'] = 'false'; // 关闭自动种子管理
        $extra_options['root_folder'] = $this->to_client->root_folder ? "true" : 'false';

        if ($this->paused) {
            $extra_options['paused'] = 'true';
        }
        if ($this->skip_check) {
            $extra_options['skip_checking'] = "true";    //转移成功，跳校验
        }

        if (empty($path)) {
            echo $help_msg;
            throw new InvalidArgumentException("{$this->from_clients->title} 的 IYUUPlus内下载器未设置种子目录，无法完成转移！" . PHP_EOL);
        }
        $torrentFile = $path . DIRECTORY_SEPARATOR . $infohash . '.torrent';
        $fast_resumePath = $path . DIRECTORY_SEPARATOR . $infohash . '.fastresume';
        $rocket->torrentDelete = $infohash;
        $rocket->torrentFile = $torrentFile;

        // 再次检查
        if (!is_file($torrentFile)) {
            //先检查是否为空
            $infohash_v1 = $move[$infohash]['infohash_v1'] ?? '';
            if (empty($infohash_v1)) {
                echo $help_msg;
                throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件{$torrentFile}不存在，infohash_v1为空，无法完成转移！");
            }

            //高版本qb下载器，infohash_v1
            $v1_path = $path . DIRECTORY_SEPARATOR . $infohash_v1 . '.torrent';
            if (is_file($v1_path)) {
                $torrentFile = $v1_path;
                $fast_resumePath = $path . DIRECTORY_SEPARATOR . $infohash_v1 . '.torrent';
                $rocket->torrentFile = $torrentFile;
            } else {
                echo $help_msg;
                throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentFile}`不存在，无法完成转移！");
            }
        }

        $metadata = file_get_contents($torrentFile);
        try {
            $parsed_torrent = Bencode::decode($metadata);
            if (empty($parsed_torrent['announce'])) {
                $needPatchTorrent = true;
            }
        } catch (ParseException $e) {
            echo '种子元数据解析失败：' . $e->getMessage() . PHP_EOL;
        }

        if ($needPatchTorrent) {
            echo '未发现tracker信息，尝试补充tracker信息...' . PHP_EOL;
            if (empty($parsed_torrent)) {
                throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentFile}`解析失败，无法完成转移！");
            }
            if (empty($parsed_torrent['announce'])) {
                if (!empty($move[$infohash]['tracker'])) {
                    $parsed_torrent['announce'] = $move[$infohash]['tracker'];
                } else {
                    if (!is_file($fast_resumePath)) {
                        echo $help_msg;
                        throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，resume文件`{$fast_resumePath}`不存在，无法完成转移！");
                    }

                    try {
                        $parsed_fast_resume = Bencode::load($fast_resumePath);
                    } catch (ParseException $e) {
                        throw new InvalidArgumentException("{$this->from_clients->title} 的`{$move[$infohash]['name']}`，resume文件`{$fast_resumePath}`解析失败`{$e->getMessage()}`，无法完成转移！");
                    }
                    $trackers = $parsed_fast_resume['trackers'];
                    if (count($trackers) > 0 && !empty($trackers[0])) {
                        if (is_array($trackers[0]) && count($trackers[0]) > 0 && !empty($trackers[0][0])) {
                            $parsed_torrent['announce'] = $trackers[0][0];
                        }
                    } else {
                        echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，resume文件`{$fast_resumePath}`不包含tracker地址，无法完成转移！";
                    }
                }
            }
            $metadata = Bencode::encode($parsed_torrent);
        }

        $contractsTorrent = new TorrentContract($metadata, true);
        $contractsTorrent->parameters = $extra_options;

        return $contractsTorrent;
    }

    /**
     * 检测qBittorrent版本号是否大于4.4.0
     * @param Clients $fromBittorrentClient
     * @return bool
     * @throws ServerErrorException
     */
    private function versionGEQ44(Clients $fromBittorrentClient): bool
    {
        if ($this->from_clients->getClientEnums() === ClientEnums::qBittorrent) {
            /** @var \Iyuu\BittorrentClient\Driver\qBittorrent\Client $fromBittorrentClient */
            $version = $fromBittorrentClient->appVersion();
            echo '您的qBittorrent版本号：' . $version . PHP_EOL . PHP_EOL;
            return version_compare(ltrim($version, 'v'), '4.4.0', '>=');
        }

        return false;
    }

    /**
     * 处理转移种子时所设置的过滤器、选择器
     * - 原理：前缀匹配
     * @param string $path
     * @return bool   true 过滤 | false 不过滤
     */
    private function pathFilter(string $path): bool
    {
        echo '当前做种的数据目录：' . var_dump($path) . PHP_EOL;

        $path = rtrim($path, "/\\");      // 提高Windows转移兼容性
        $path_filter = $this->path_filter;
        $path_selector = $this->path_selector;
        if (empty($path_filter) && empty($path_selector)) {
            return false;
        }

        switch (true) {
            case empty($path_filter):
                // 仅设置 选择器
                foreach ($path_selector as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        return false;
                    }
                }
                echo '已跳过！转移选择器未匹配到：' . $path . PHP_EOL;
                return true;
            case empty($path_selector):
                // 仅设置 过滤器
                foreach ($path_filter as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        echo '已跳过！转移过滤器匹配到：' . $path . PHP_EOL;
                        return true;
                    }
                }
                return false;
            default:
                // 先过滤器
                foreach ($path_filter as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        echo '已跳过！转移过滤器匹配到：' . $path . PHP_EOL;
                        return true;
                    }
                }
                // 后选择器
                foreach ($path_selector as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        return false;
                    }
                }
                echo '已跳过！转移选择器未匹配到：' . $path . PHP_EOL;
                return true;
        }
    }

    /**
     * 实际路径与相对路径之间互相转换
     * - 原理：前缀匹配成功时，执行操作
     * @param string $path
     * @return string | null        string转换成功
     */
    private function pathConvert(string $path): ?string
    {
        if ($this->path_convert_type === PathConvertTypeEnums::Eq) {
            return $path;
        }

        $path = rtrim($path, "/\\");      // 提高Windows转移兼容性
        foreach ($this->path_convert_rule as $key => $val) {
            if (str_starts_with($path, $key)) {
                return match ($this->path_convert_type) {
                    PathConvertTypeEnums::Add => $val . $path,
                    PathConvertTypeEnums::Sub => substr($path, strlen($key)),
                    PathConvertTypeEnums::Replace => $val . substr($path, strlen($key)),
                    default => $path,
                };
            }
        }
        return null;
    }

    /**
     * 解析任务
     * @return array
     */
    private function parseCrontab(): array
    {
        $crontabModel = Crontab::find($this->crontab_id);
        if (!$crontabModel) {
            throw new InvalidArgumentException('计划任务数据不存在');
        }

        $parameter = $crontabModel->parameter;
        if (is_string($parameter)) {
            $parameter = json_decode($parameter, true);
        }

        $marker = DownloaderMarkerEnums::from($parameter['marker'] ?? DownloaderMarkerEnums::Empty->value);
        return [$crontabModel, $parameter, $marker];
    }

    /**
     * 解析其他调用参数
     * @return void
     */
    private function parseOthers(): void
    {
        // 来源下载器
        $this->from_clients = ClientServices::getClient($this->getParameter('from_clients'));

        // 目标下载器
        $this->to_client = ClientServices::getClient($this->getParameter('to_clients'));

        // 路径过滤器
        if ($path_filter = $this->getParameter('path_filter')) {
            $this->path_filter = Folder::whereIn('folder_id', explode(',', $path_filter))->pluck('folder_value')->toArray();
        }

        // 路径选择器
        if ($path_selector = $this->getParameter('path_selector')) {
            $this->path_selector = Folder::whereIn('folder_id', explode(',', $path_selector))->pluck('folder_value')->toArray();
        }

        // 路径转换类型
        $this->path_convert_type = PathConvertTypeEnums::Eq;

        // 路径转换规则、类型
        if ($path_convert_rule = $this->getParameter('path_convert_rule')) {
            $rules = self::parsePathConvertRule($path_convert_rule);
            if (!empty($rules)) {
                // 路径转换类型
                $this->path_convert_type = PathConvertTypeEnums::from($this->getParameter('path_convert_type'));
                // 路径转换规则
                $this->path_convert_rule = $rules;
            }
        }

        // 跳校验
        $this->skip_check = Utils::booleanParse($this->getParameter('skip_check', false));

        // 暂停
        $this->paused = Utils::booleanParse($this->getParameter('paused', false));

        // 删除源做种
        $this->delete_torrent = Utils::booleanParse($this->getParameter('delete_torrent', false));
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

    /**
     * 处理Linux、Windows换行符差异
     * @param string $str
     * @return string
     */
    private static function replaceBr(string $str): string
    {
        while (str_contains($str, "\r\n")) {
            $str = str_replace("\r\n", "\n", $str);
        }
        return $str;
    }

    /**
     * 解析路径转换规则
     * @param string $path_convert_rule
     * @return array
     */
    private static function parsePathConvertRule(string $path_convert_rule): array
    {
        $rules = [];
        $lines = explode("\n", self::replaceBr($path_convert_rule));
        if (count($lines)) {
            foreach ($lines as $value) {
                // 跳过空行
                if (empty($value)) {
                    continue;
                }

                // 检查分隔符
                if (str_contains($value, self::Delimiter)) {
                    $item = explode(self::Delimiter, $value);
                    if (count($item) === 2) {
                        $item = array_map(function ($v) {
                            return trim($v);
                        }, $item);
                        if ($item[0]) {
                            $rules[$item[0]] = $item[1];     //关联数组
                        }
                    }
                } else {
                    if (trim($value)) {
                        $rules[trim($value)] = '';   //允许值为空
                    }
                }
            }
        }
        return $rules;
    }
}
