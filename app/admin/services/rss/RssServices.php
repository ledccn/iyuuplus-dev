<?php

namespace app\admin\services\rss;

use app\admin\services\client\ClientServices;
use app\model\Client;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\LogicEnums;
use InvalidArgumentException;
use plugin\cron\app\model\Crontab;

/**
 * RSS订阅服务
 */
readonly class RssServices
{
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
     * RSS地址
     * @var string
     */
    protected string $rss_url;
    /**
     * 数据模型：来源下载器
     * @var Client
     */
    protected Client $client;
    /**
     * 计划任务：标记规则
     * @var DownloaderMarkerEnums
     */
    protected DownloaderMarkerEnums $downloaderMarkerEnums;
    /**
     * 保存路径
     * @var string
     */
    protected string $save_path;
    /**
     * 种子大小逻辑
     * @var SizeLogic
     */
    protected SizeLogic $sizeLogic;
    /**
     * 标题副标题匹配逻辑
     * @var MatchTitleLogic
     */
    protected MatchTitleLogic $matchLogic;

    /**
     * 构造函数
     * @param int $crontab_id
     */
    public function __construct(public int $crontab_id)
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

        // 种子大小
        $this->sizeLogic = new SizeLogic(
            $this->getParameter('size_min', ''),
            $this->getParameter('size_min_unit', 'GB'),
            $this->getParameter('size_max', ''),
            $this->getParameter('size_max_unit', 'GB'),
        );

        // 解析：规则模式优先级，默认 简易模式 > 正则模式
        $ruleModeEnums = !empty($parameter['text_selector']) || !empty($parameter['text_filter']) ? RuleModeEnums::Simple : null;
        if (is_null($ruleModeEnums)) {
            $ruleModeEnums = !empty($parameter['regex_selector']) || !empty($parameter['regex_filter']) ? RuleModeEnums::Regex : null;
        }

        // 匹配规则设置
        $this->matchLogic = new MatchTitleLogic(
            $ruleModeEnums,
            $parameter['text_selector'] ?? [],
            LogicEnums::create($parameter['text_selector_op'] ?? ''),
            $parameter['text_filter'] ?? [],
            LogicEnums::create($parameter['text_filter_op'] ?? ''),
            $parameter['regex_selector'] ?? [],
            $parameter['regex_filter'] ?? [],
        );
    }

    /**
     * 执行
     * @return void
     */
    public function run(): void
    {
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
