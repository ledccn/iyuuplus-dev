<?php

namespace app\admin\services\transfer;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\Folder;
use InvalidArgumentException;
use Iyuu\BittorrentClient\ClientEnums;
use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent as TorrentContract;
use Iyuu\BittorrentClient\Exception\ServerErrorException;
use Iyuu\BittorrentClient\Utils;
use plugin\cron\app\model\Crontab;
use Rhilip\Bencode\Bencode;
use Rhilip\Bencode\ParseException;
use Webman\Event\Event;

/**
 * 自动转移做种客户端服务类
 */
class TransferServices
{
    /**
     * 路径分隔符
     */
    public const string Delimiter = '{**}';
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
        [$this->crontabModel, $this->parameter] = $this->parseCrontab();
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

        // qb版本大于4.4
        $qBittorrent_version_lg_4_4 = false;
        if ($this->from_clients->getClientEnums() === ClientEnums::qBittorrent) {
            /** @var \Iyuu\BittorrentClient\Driver\qBittorrent\Client $toBittorrentClient */
            $version = $toBittorrentClient->appVersion();
            $arr = explode('.', ltrim($version, "v"), 3);
            if (count($arr) > 2 && ($arr[0] == '4' && $arr[1] >= '4' || $arr[0] > '4')) {
                $qBittorrent_version_lg_4_4 = true;
            }
        }

        echo "正在从 {$this->from_clients->title} 下载器获取当前做种hash..." . PHP_EOL;

        $torrentList = $fromBittorrentClient->getTorrentList();
        $hashDict = $torrentList['hashString'];   // 哈希目录字典
        $move = $torrentList[Clients::TORRENT_LIST];

        $help_msg = 'IYUU自动转移做种客户端--使用教程' . PHP_EOL . 'https://www.iyuu.cn/archives/451/' . PHP_EOL . 'https://www.iyuu.cn/archives/465/' . PHP_EOL;

        //$total = count($hashDict);
        Event::dispatch('transfer.action.before', [$hashDict, $toBittorrentClient, $this->from_clients]);
        // 第一层循环：哈希目录字典
        foreach ($hashDict as $infohash => $downloadDir) {
            if ($this->pathFilter($downloadDir)) {
                continue;
            }

            // 做种实际路径与相对路径之间互转
            echo '转换前：' . $downloadDir . PHP_EOL;
            $downloadDir = $this->pathReplace($downloadDir);
            echo '转换后：' . $downloadDir . PHP_EOL;
            if (empty($downloadDir)) {
                echo '路径转换参数配置错误，请重新配置！！！' . PHP_EOL;
                return;
            }

            // 用户配置的种子目录
            $path = $this->from_clients->torrent_path;
            $torrentPath = '';
            $fast_resumePath = '';
            $needPatchTorrent = $qBittorrent_version_lg_4_4;
            // 待删除种子
            $torrentDelete = '';
            // 附加参数
            $extra_options = [];
            // 获取种子文件的实际路径
            switch ($this->from_clients->getClientEnums()) {
                case ClientEnums::transmission:
                    $extra_options['paused'] = $this->paused;

                    // 优先使用API提供的种子路径
                    $torrentPath = $move[$infohash]['torrentFile'];
                    $torrentDelete = $move[$infohash]['id'];
                    // API提供的种子路径不存在时，使用配置内指定的BT_backup路径
                    if (!is_file($torrentPath)) {
                        $torrentPath = str_replace("\\", "/", $torrentPath);
                        $torrentPath = $path . strrchr($torrentPath, '/');
                    }
                    // 再次检查
                    if (!is_file($torrentPath)) {
                        echo $help_msg;
                        echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentPath}`不存在，无法完成转移！";
                        return;
                    }
                    break;
                case ClientEnums::qBittorrent:
                    $extra_options['paused'] = $this->paused ? 'true' : 'false';
                    if ($this->skip_check) {
                        $extra_options['skip_checking'] = "true";    //转移成功，跳校验
                    }

                    if (empty($path)) {
                        echo $help_msg;
                        echo "{$this->from_clients->title} 的 IYUUPlus内下载器未设置种子目录，无法完成转移！" . PHP_EOL;
                        return;
                    }
                    $torrentPath = $path . DIRECTORY_SEPARATOR . $infohash . '.torrent';
                    $fast_resumePath = $path . DIRECTORY_SEPARATOR . $infohash . '.fastresume';
                    $torrentDelete = $infohash;

                    // 再次检查
                    if (!is_file($torrentPath)) {
                        //先检查是否为空
                        $infohash_v1 = $move[$infohash]['infohash_v1'] ?? '';
                        if (empty($infohash_v1)) {
                            echo $help_msg;
                            echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件{$torrentPath}不存在，infohash_v1为空，无法完成转移！";
                            return;
                        }

                        //高版本qb下载器，infohash_v1
                        $v1_path = $path . DIRECTORY_SEPARATOR . $infohash_v1 . '.torrent';
                        if (is_file($v1_path)) {
                            $torrentPath = $v1_path;
                            $fast_resumePath = $path . DIRECTORY_SEPARATOR . $infohash_v1 . '.torrent';
                        } else {
                            echo $help_msg;
                            echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentPath}`不存在，无法完成转移！";
                        }
                    }
                    break;
                default:
                    break;
            }

            //读取种子源文件
            echo '存在种子：' . $torrentPath . PHP_EOL;
            $torrent = file_get_contents($torrentPath);
            $parsed_torrent = [];
            try {
                $parsed_torrent = Bencode::decode($torrent);
                if (empty($parsed_torrent['announce'])) {
                    $needPatchTorrent = true;
                }
            } catch (ParseException $e) {
            }

            if ($needPatchTorrent) {
                echo '未发现tracker信息，尝试补充tracker信息...' . PHP_EOL;
                if (empty($parsed_torrent)) {
                    echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，种子文件`{$torrentPath}`解析失败，无法完成转移！";
                    return;
                }
                if (empty($parsed_torrent['announce'])) {
                    if (!empty($move[$infohash]['tracker'])) {
                        $parsed_torrent['announce'] = $move[$infohash]['tracker'];
                    } else {
                        if (!is_file($fast_resumePath)) {
                            echo $help_msg;
                            echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，resume文件`{$fast_resumePath}`不存在，无法完成转移！";
                            return;
                        }
                        $parsed_fast_resume = null;
                        try {
                            $parsed_fast_resume = Bencode::load($fast_resumePath);
                        } catch (ParseException $e) {
                            echo "{$this->from_clients->title} 的`{$move[$infohash]['name']}`，resume文件`{$fast_resumePath}`解析失败`{$e->getMessage()}`，无法完成转移！";
                            return;
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
                $torrent = Bencode::encode($parsed_torrent);
            }

            // 构造种子对象
            $contractsTorrent = new TorrentContract($torrent, true);
            $contractsTorrent->savePath = $downloadDir;
            $contractsTorrent->parameters = $extra_options;
            // 正式开始转移
            echo "将把种子文件推送给下载器，正在转移做种客户端..." . PHP_EOL;
            $ret = $toBittorrentClient->addTorrent($contractsTorrent);

            /**
             * 转移成功的种子写日志
             */
            //$log = $infohash . PHP_EOL . $torrentPath . PHP_EOL . $downloadDir . PHP_EOL . PHP_EOL;
            if ($ret) {
                //转移成功时，删除做种，不删资源
                if ($this->delete_torrent) {
                    $fromBittorrentClient->delete($torrentDelete);
                }
            } else {
                // 失败的种子
            }
        }
    }

    /**
     * 处理转移种子时所设置的过滤器、选择器
     * @param string $path
     * @return bool   true 过滤 | false 不过滤
     */
    private function pathFilter(string $path): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);      // 提高Windows转移兼容性
        // 转移过滤器、选择器 David/2020年7月11日
        $path_filter = $this->path_filter;
        $path_selector = $this->path_selector;
        if (empty($path_filter) && empty($path_selector)) {
            return false;
        }

        if (empty($path_filter)) {
            //选择器
            foreach ($path_selector as $pathName) {
                if (str_starts_with($path, $pathName)) {      // 没用$path == $key判断，是为了提高兼容性
                    return false;
                }
            }
            echo '已跳过！转移选择器未匹配到：' . $path . PHP_EOL;
            return true;
        } elseif (empty($path_selector)) {
            //过滤器
            foreach ($path_filter as $pathName) {
                if (str_starts_with($path, $pathName)) {      // 没用$path == $key判断，是为了提高兼容性
                    echo '已跳过！转移过滤器匹配到：' . $path . PHP_EOL;
                    return true;
                }
            }
            return false;
        } else {
            //同时设置过滤器、选择器
            //先过滤器
            foreach ($path_filter as $pathName) {
                if (str_starts_with($path, $pathName)) {
                    echo '已跳过！转移过滤器匹配到：' . $path . PHP_EOL;
                    return true;
                }
            }
            //后选择器
            foreach ($path_selector as $pathName) {
                if (str_starts_with($path, $pathName)) {
                    return false;
                }
            }
            echo '已跳过！转移选择器未匹配到：' . $path . PHP_EOL;

            return true;
        }
    }

    /**
     * 实际路径与相对路径之间互相转换
     * @param string $path
     * @return string | null        string转换成功
     */
    private function pathReplace(string $path): ?string
    {
        $pathArray = $this->path_convert_rule;
        $path = rtrim($path, DIRECTORY_SEPARATOR);      // 提高Windows转移兼容性
        switch ($this->path_convert_type->value) {
            case PathConvertTypeEnums::Sub->value:          // 减
                foreach ($pathArray as $key => $val) {
                    if (str_starts_with($path, $key)) {
                        return substr($path, strlen($key));
                    }
                }
                break;
            case PathConvertTypeEnums::Add->value:          // 加
                foreach ($pathArray as $key => $val) {
                    if (str_starts_with($path, $key)) {     // 没用$path == $key判断，是为了提高兼容性
                        return $val . $path;
                    }
                }
                break;
            case PathConvertTypeEnums::Replace->value:      // 替换
                foreach ($pathArray as $key => $val) {
                    if (str_starts_with($path, $key)) {     // 没用$path == $key判断，是为了提高兼容性
                        return $val . substr($path, strlen($key));
                    }
                }
                break;
            default:        // 不变
                return $path;
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


        return [$crontabModel, $parameter];
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
            $this->path_filter = Folder::whereIn('folder_id', $path_filter)->pluck('folder_value')->toArray();
        }

        // 路径选择器
        if ($path_selector = $this->getParameter('path_selector')) {
            $this->path_selector = Folder::whereIn('folder_id', $path_selector)->pluck('folder_value')->toArray();
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
        $this->skip_check = Utils::booleanParse($this->getParameter('skip_check'));

        // 暂停
        $this->paused = Utils::booleanParse($this->getParameter('paused'));

        // 删除源做种
        $this->delete_torrent = Utils::booleanParse($this->getParameter('delete_torrent'));
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
