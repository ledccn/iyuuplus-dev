<?php

namespace app\admin\services\reseed;

use app\command\ReseedCommand;
use app\model\enums\DownloaderMarkerEnums;
use app\model\enums\NotifyChannelEnums;
use Error;
use Exception;
use Ledc\Element\GenerateInterface;
use plugin\cron\app\interfaces\CrontabAbstract;
use plugin\cron\app\model\CrontabLog;
use plugin\cron\app\services\CrontabRocket;
use plugin\cron\app\services\Scheduler;
use plugin\cron\app\support\PushNotify;
use Symfony\Component\Process\Process;
use Throwable;
use Workerman\Crontab\Crontab as WorkermanCrontab;
use Workerman\Timer;

/**
 * 计划任务：自动辅种配置模板
 */
class ReseedTemplate extends CrontabAbstract
{
    /**
     * 枚举条目转为数组
     *  - 文本描述 => 值
     * @return array
     */
    public static function select(): array
    {
        return ReseedSelectEnums::select();
    }

    /**
     * 生成Layui计划任务配置模板
     * @param int $type
     * @return GenerateInterface|null
     */
    public function generate(int $type): ?GenerateInterface
    {
        return match ($type) {
            ReseedSelectEnums::reseed->value => $this,
            default => null,
        };
    }

    /**
     * 启动器
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab|null
     */
    public function start(CrontabRocket $rocket): ?WorkermanCrontab
    {
        $model = $rocket->model;
        if ((int)$model->task_type === ReseedSelectEnums::reseed->value) {
            return new WorkermanCrontab($model->rule, function () use ($model, $rocket) {
                $startTime = microtime(true);
                $time = time();
                try {
                    if ($rocket->getProcess()?->isRunning()) {
                        echo '当前辅种任务运行中，本轮忽略！' . PHP_EOL;
                        PushNotify::info(sprintf('任务d%运行中，本轮忽略', $model->crontab_id));
                        return;
                    }

                    $command = [PHP_BINARY, base_path('webman'), $model->target, $model->crontab_id];
                    $process = new Process($command, base_path());
                    $process->start();
                    $rocket->setProcess($process);
                    $timer_id = Timer::add(0.5, function () use ($rocket, $process, &$timer_id, $startTime) {
                        $code = 0;
                        $exception = '';
                        try {
                            $isDelete = !$process->isRunning();
                            if ($out = $process->getIncrementalOutput()) {
                                send_shell_output($rocket->model->crontab_id, $out);
                            }
                        } catch (Error|Exception|Throwable $throwable) {
                            $code = $throwable->getCode() ?: Scheduler::DEFAULT_ERROR_CODE;
                            $exception = $throwable->getMessage();
                            $isDelete = true;
                        } finally {
                            if ($isDelete) {
                                Timer::del($timer_id);
                                $rocket->setProcess(null);
                                $endTime = microtime(true);
                                CrontabLog::createCrontabLog($rocket->model, $exception ?: '进程运行结束', $code, ($endTime - $startTime) * 1000);
                            }
                        }
                    });
                } catch (Error|Exception|Throwable $throwable) {
                    $code = $throwable->getCode() ?: Scheduler::DEFAULT_ERROR_CODE;
                    $message = $throwable->getMessage();
                    $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                    send_shell_output($model->crontab_id, $exception);
                } finally {
                    $model->updateRunning($time);
                }
            }, $model->crontab_id);
        }
        return null;
    }

    /**
     * @return string
     */
    public function html(): string
    {
        $command = ReseedCommand::COMMAND_NAME;
        $markerEmpty = DownloaderMarkerEnums::Empty->value;
        $markerTag = DownloaderMarkerEnums::Tag->value;
        $markerCategory = DownloaderMarkerEnums::Category->value;
        $notify_iyuu = NotifyChannelEnums::notify_iyuu->value;
        $notify_server_chan = NotifyChannelEnums::notify_server_chan->value;
        $notify_bark = NotifyChannelEnums::notify_bark->value;
        $notify_qy_weixin = NotifyChannelEnums::notify_qy_weixin->value;
        $notify_webhook = NotifyChannelEnums::notify_webhook->value;
        return PHP_EOL . <<<EOF
<style>
.layui-input-wrap {
    width: 60px !important;
    line-height: 20px !important;
}
</style>
<div class="layui-form-item layui-hide">
    <label class="layui-form-label required">命令名称</label>
    <div class="layui-input-block">
        <input type="text" name="target" value="$command" required lay-verify="required" placeholder="请输入命令名称" class="layui-input" readonly>
    </div>
</div>
<div name="parameter" id="parameter" value="" class="layui-hide"></div>
<div class="layui-form-item">
    <label class="layui-form-label required">辅种站点</label>
    <div class="layui-input-block">
        <div name="sites" id="sites" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label required">辅种下载器</label>
    <div class="layui-input-block">
        <div name="clients" id="clients" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">路径过滤器</label>
    <div class="layui-input-block">
        <div name="parameter[path_filter]" id="path_filter" value=""></div>
        <div class="layui-form-mid layui-text-em">排除目录内的资源，不辅种</div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label required">通知渠道</label>
    <div class="layui-input-block">
        <input type="radio" name="parameter[notify_channel]" value="" title="不通知" checked>
        <input type="radio" name="parameter[notify_channel]" value="$notify_iyuu" title="爱语飞飞">
        <input type="radio" name="parameter[notify_channel]" value="$notify_server_chan" title="Server酱">
        <input type="radio" name="parameter[notify_channel]" value="$notify_bark" title="Bark">
        <input type="radio" name="parameter[notify_channel]" value="$notify_qy_weixin" title="企业微信群机器人">
        <input type="radio" name="parameter[notify_channel]" value="$notify_webhook" title="自定义通知">
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label required" title="辅种成功后，对添加的种子做标记">标记规则</label>
    <div class="layui-input-block">
        <input type="radio" name="parameter[marker]" value="$markerEmpty" title="不操作" checked>
        <input type="radio" name="parameter[marker]" value="$markerTag" title="标记标签">
        <input type="radio" name="parameter[marker]" value="$markerCategory" title="标记分类">
    </div>
    <div class="layui-form-mid layui-text-em">辅种成功后，对种子做标记（需要下载器支持）</div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">自动校验</label>
    <div class="layui-input-inline layui-input-wrap">
        <input type="checkbox" name="parameter[auto_check]" lay-skin="switch" title="ON|OFF" lay-filter="auto_check" id="auto_check">
    </div>
    <div class="layui-form-mid layui-text-em">此功能在TR以及低版本QB中属于默认行为，是否勾选都会自动校验</div>
</div>
<!-- 辅种站点模板 -->
<script type="text/html" id="sites_tpl">
<div class="layui-col-xs12 layui-col-space10">
    <div class="layui-col-xs6 layui-col-sm4 layui-col-md3">
        <button type="button" class="layui-btn layui-btn-sm layui-btn-fluid layui-btn-radius" lay-on="select_all" id="select_all">全选</button>
    </div>
    <div class="layui-col-xs6 layui-col-sm4 layui-col-md3">
        <button type="button" class="layui-btn layui-btn-sm layui-btn-fluid layui-btn-radius layui-btn-normal" lay-on="select_invert">反选</button>
    </div>
</div>
{{#  layui.each(d, function(index, item){ }}
<div class="layui-col-xs6 layui-col-sm4 layui-col-md3 layui-col-lg2">
    <input type="checkbox" name="parameter[sites][{{= item.value }}]" title="{{= item.name }}" lay-skin="tag">
</div>
{{#  }); }}
</script>
<!-- 辅种下载器模板 -->
<script type="text/html" id="clients_tpl">
{{#  layui.each(d, function(index, item){ }}
<div class="layui-col-xs6 layui-col-sm4 layui-col-md3 layui-col-lg2">
    <input type="checkbox" name="parameter[clients][{{= item.value }}]" title="{{= item.name }}" lay-skin="tag">
</div>
{{#  }); }}
</script>

EOF;
    }

    /**
     * @return string
     */
    public function js(): string
    {
        return PHP_EOL . file_get_contents(__DIR__ . '/reseed.js');
    }
}
