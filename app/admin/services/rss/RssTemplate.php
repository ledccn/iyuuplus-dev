<?php

namespace app\admin\services\rss;

use app\model\enums\DownloaderMarkerEnums;
use Ledc\Element\GenerateInterface;
use plugin\cron\app\interfaces\CrontabAbstract;
use plugin\cron\app\services\CrontabRocket;
use Workerman\Crontab\Crontab as WorkermanCrontab;

class RssTemplate extends CrontabAbstract
{
    public static function select(): array
    {
        return RssSelectEnums::select();
    }

    public function generate(int $type): ?GenerateInterface
    {
        return match ($type) {
            RssSelectEnums::rss->value => $this,
            default => null
        };
    }

    /**
     * 启动器
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab|null
     */
    public function start(CrontabRocket $rocket): ?WorkermanCrontab
    {
        return static::startCrontab(RssSelectEnums::rss->value, 'RSS订阅', $rocket);
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $command = 'iyuu:rss';
        $markerEmpty = DownloaderMarkerEnums::Empty->value;
        $markerTag = DownloaderMarkerEnums::Tag->value;
        $markerCategory = DownloaderMarkerEnums::Category->value;
        return PHP_EOL . <<<EOF
<script src="/parameter.js"></script>
<div class="layui-form-item layui-hide">
    <label class="layui-form-label required">命令名称</label>
    <div class="layui-input-block">
        <input type="text" name="target" value="$command" required lay-verify="required" placeholder="请输入命令名称" class="layui-input" readonly>
    </div>
</div>
<div name="parameter" id="parameter" value="" class="layui-hide"></div>
<div class="layui-form-item">
    <label class="layui-form-label required">下载器</label>
    <div class="layui-input-block">
        <div name="parameter[client_id]" id="client_id" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label required" title="添加下载任务时，对种子做标记">标记规则</label>
    <div class="layui-input-block">
        <input type="radio" name="parameter[marker]" value="$markerEmpty" title="不操作" checked>
        <input type="radio" name="parameter[marker]" value="$markerTag" title="标记标签">
        <input type="radio" name="parameter[marker]" value="$markerCategory" title="标记分类">
    </div>
    <div class="layui-form-mid layui-text-em">添加下载任务时，对种子做标记（需要下载器支持）</div>
</div>
EOF;
    }

    /**
     * @return string
     */
    public function js(): string
    {
        return PHP_EOL . file_get_contents(__DIR__ . '/rss.js');
    }
}
