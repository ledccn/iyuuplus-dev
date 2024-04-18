<?php

namespace app\admin\services\transfer;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\Folder;
use InvalidArgumentException;
use Iyuu\BittorrentClient\Utils;
use plugin\cron\app\model\Crontab;
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
     */
    public function run(): void
    {
        // 来源
        $fromBittorrentClient = ClientServices::createBittorrent($this->from_clients);
        // 目标
        $toBittorrentClient = ClientServices::createBittorrent($this->to_client);

        echo "正在从 {$this->from_clients->title} 下载器获取当前做种hash..." . PHP_EOL;

        $torrentList = $fromBittorrentClient->getTorrentList();
        $hashDict = $torrentList['hashString'];   // 哈希目录字典
        //$total = count($hashDict);
        Event::dispatch('transfer.action.before', [$hashDict, $toBittorrentClient, $this->from_clients]);
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
