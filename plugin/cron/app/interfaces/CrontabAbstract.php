<?php

namespace plugin\cron\app\interfaces;

use app\admin\services\transfer\TransferSelectEnums;
use Error;
use Exception;
use Ledc\Element\GenerateInterface;
use plugin\cron\app\model\Crontab;
use plugin\cron\app\model\CrontabLog;
use plugin\cron\app\services\CrontabRocket;
use plugin\cron\app\services\Scheduler;
use plugin\cron\app\support\PushNotify;
use Symfony\Component\Process\Process;
use Throwable;
use Workerman\Crontab\Crontab as WorkermanCrontab;
use Workerman\Timer;

/**
 * 计划任务抽象类
 */
abstract class CrontabAbstract implements CrontabTaskTypeEnumsInterface, CrontabLayuiTemplateInterface, CrontabSchedulerInterface, GenerateInterface
{
    /**
     * 获取任务最大执行时间
     * @param int $task_type
     * @return int
     */
    protected static function getMaxExecutionTime(int $task_type): int
    {
        return match ($task_type) {
            TransferSelectEnums::transfer->value => Crontab::MAX_EXECUTION_TIME * 3,
            default => Crontab::MAX_EXECUTION_TIME
        };
    }

    /**
     * @param int $value
     * @param string $tips
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab|null
     */
    protected static function startCrontab(int $value, string $tips, CrontabRocket $rocket): ?WorkermanCrontab
    {
        $model = $rocket->model;
        if ($value === (int)$model->task_type) {
            return new WorkermanCrontab($model->rule, function () use ($model, $rocket, $tips) {
                $maxExecutionTime = self::getMaxExecutionTime((int)$model->task_type);
                $startTime = microtime(true);
                $time = time();
                try {
                    if ($rocket->getProcess() || $rocket->getProcess()->isRunning()) {
                        echo '当前' . $tips . '运行中，本轮忽略！' . PHP_EOL;
                        PushNotify::info(sprintf('任务d%运行中，本轮忽略', $model->crontab_id));
                        return;
                    }

                    $command = [PHP_BINARY, base_path('webman'), $model->target, $model->crontab_id];
                    $process = new Process($command, base_path(), null, null, $maxExecutionTime + 300);
                    $process->start();
                    $rocket->setProcess($process);
                    $timer_id = Timer::add(0.5, function () use ($rocket, $process, &$timer_id, $startTime, $maxExecutionTime) {
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
                            $endTime = microtime(true);
                            $duration = $endTime - $startTime;
                            // 删除定时器、停止进程
                            if ($isDelete || $maxExecutionTime <= $duration) {
                                Timer::del($timer_id);
                                $rocket->stopProcess();
                                CrontabLog::createCrontabLog($rocket->model, $exception ?: '进程运行结束', $code, $duration * 1000);
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
}
