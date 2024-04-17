<?php

namespace app\admin\services\transfer;

use app\command\TransferCommand;
use Ledc\Element\GenerateInterface;
use plugin\cron\app\interfaces\CrontabAbstract;
use plugin\cron\app\model\CrontabLog;
use plugin\cron\app\services\CrontabRocket;
use plugin\cron\app\services\Scheduler;
use plugin\cron\app\support\PushNotify;
use Symfony\Component\Process\Process;
use Workerman\Crontab\Crontab as WorkermanCrontab;
use Workerman\Timer;

/**
 * 计划任务：自动转移做种客户端配置模板
 */
class TransferTemplate extends CrontabAbstract
{
    /**
     * 枚举条目转为数组
     *  - 文本描述 => 值
     * @return array
     */
    public static function select(): array
    {
        return TransferSelectEnums::select();
    }

    /**
     * 生成Layui计划任务配置模板
     * @param int $type
     * @return GenerateInterface|null
     */
    public function generate(int $type): ?GenerateInterface
    {
        return match ($type) {
            TransferSelectEnums::transfer->value => $this,
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
        $model = $rocket->model;
        if (TransferSelectEnums::transfer->value === (int)$model->task_type) {
            return new WorkermanCrontab($model->rule, function () use ($model, $rocket) {
                $startTime = microtime(true);
                $time = time();
                try {
                    if ($rocket->getProcess()?->isRunning()) {
                        echo '当前自动转移运行中，本轮忽略！' . PHP_EOL;
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
                        } catch (\Error|\Exception|\Throwable $throwable) {
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
                } catch (\Error|\Exception|\Throwable $throwable) {
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
        $command = TransferCommand::COMMAND_NAME;
        return PHP_EOL . <<<EOF
<style>
.layui-form-label {
    width: 90px;
}
.layui-input-block {
    margin-left: 120px;
}
.layui-input-wrap {
    width: 50px !important;
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
    <label class="layui-form-label required" title="当前正常做种的下载器">来源下载器</label>
    <div class="layui-input-block">
        <div name="parameter[from_clients]" id="from_clients" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label required" title="即将转移到的下载器">目标下载器</label>
    <div class="layui-input-block">
        <div name="parameter[to_clients]" id="to_clients" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label" title="不转移此目录内的种子">过滤器</label>
    <div class="layui-input-block">
        <div name="parameter[path_filter]" id="path_filter" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label" title="仅转移此目录内的种子">选择器</label>
    <div class="layui-input-block">
        <div name="parameter[path_selector]" id="path_selector" value=""></div>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label" title="用于相对路径与绝对路径之间互相转换，实现种子对应资源目录，是客户端之间转移做种的重要步骤">路径转换类型</label>
    <div class="layui-input-block">
        <input type="radio" name="parameter[path_convert_type]" value="0" title="相等" checked>
        <input type="radio" name="parameter[path_convert_type]" value="1" title="减">
        <input type="radio" name="parameter[path_convert_type]" value="2" title="加">
        <input type="radio" name="parameter[path_convert_type]" value="3" title="替换">
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">路径转换规则</label>
    <div class="layui-input-block">
        <textarea name="parameter[path_convert_rule]" placeholder="请输入路径转换规则，每一行代表一条规则；" class="layui-textarea"></textarea>
    </div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">跳校验</label>
    <div class="layui-input-inline layui-input-wrap">
        <input type="checkbox" name="parameter[skip_check]" lay-skin="switch" lay-text="YES|NO" lay-filter="skip_check" id="skip_check">
    </div>
    <div class="layui-form-mid layui-text-em">转移时，跳过校验（此功能需要下载器支持）</div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">暂停</label>
    <div class="layui-input-inline layui-input-wrap">
        <input type="checkbox" name="parameter[paused]" lay-skin="switch" lay-text="YES|NO" lay-filter="paused" id="paused">
    </div>
    <div class="layui-form-mid layui-text-em">转移后，不要自动开始</div>
</div>
<div class="layui-form-item">
    <label class="layui-form-label">删除源做种</label>
    <div class="layui-input-inline layui-input-wrap">
        <input type="checkbox" name="parameter[delete_torrent]" lay-skin="switch" lay-text="YES|NO" lay-filter="delete_torrent" id="delete_torrent">
    </div>
    <div class="layui-form-mid layui-text-em">转移后，删除来源下载器内的种子<span class="layui-badge">风险提示：第一次转移时请不要勾选，万一路径配置错误，将会删除客户端正常做种的种子。非必要，请勿勾选！！！</span></div>
</div>
EOF;
    }

    /**
     * @return string
     */
    public function js(): string
    {
        return PHP_EOL . file_get_contents(__DIR__ . '/transfer.js');
    }
}