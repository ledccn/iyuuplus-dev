<?php

namespace plugin\cron\app\services;

use Error;
use Exception;
use InvalidArgumentException;
use Ledc\Curl\Curl;
use plugin\cron\api\AsyncWorker;
use plugin\cron\api\CrontabExtend;
use plugin\cron\app\model\Crontab;
use plugin\cron\app\model\CrontabLog;
use plugin\cron\app\services\generates\CrontabTaskTypeEnums;
use plugin\cron\app\support\PushNotify;
use Throwable;
use Workerman\Crontab\Crontab as WorkermanCrontab;
use Workerman\Timer;

/**
 * 计划任务
 */
class Scheduler
{
    /**
     * 默认错误码
     */
    public const int DEFAULT_ERROR_CODE = 250;

    /**
     * 启动当前计划任务
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    public function start(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return match ((int)$model->task_type) {
            CrontabTaskTypeEnums::command->value => $this->startWebmanCommand($rocket),
            CrontabTaskTypeEnums::classMethod->value => $this->startClassMethod($rocket),
            CrontabTaskTypeEnums::urlRequest->value => $this->startUrlRequest($rocket),
            CrontabTaskTypeEnums::evalCode->value => $this->startEvalCode($rocket),
            CrontabTaskTypeEnums::shellExec->value => $this->startShellExec($rocket),
            default => $this->extend($rocket)
        };
    }

    /**
     * 扩展支持更多的计划任务类型
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function extend(CrontabRocket $rocket): WorkermanCrontab
    {
        $extend = CrontabExtend::getInstance();
        foreach ($extend->getSchedulers() as $scheduler) {
            if ($crontab = $scheduler->start($rocket)) {
                return $crontab;
            }
        }

        $model = $rocket->model;
        throw new InvalidArgumentException(sprintf(
            '未适配当前任务类型：%s | 任务ID：%d | 任务标题：%s',
            $model->task_type,
            $model->crontab_id,
            $model->title
        ));
    }

    /**
     * 【启动计划任务】webman命令
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function startWebmanCommand(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return new WorkermanCrontab($model->rule, function () use ($model, $rocket) {
            $startTime = microtime(true);
            $time = time();
            $code = 0;
            $exception = '';
            try {
                if ($rocket->getProcess() || $rocket->getProcess()?->isRunning()) {
                    echo '当前任务运行中，本轮忽略！' . PHP_EOL;
                    PushNotify::info(sprintf('任务d%运行中，本轮忽略', $model->crontab_id));
                    return;
                }

                $process = Crontab::runWebmanCommand($model);
                $rocket->setProcess($process);
                $timer_id = Timer::add(0.5, function () use ($rocket, $process, &$timer_id, $startTime, &$code, &$exception) {
                    try {
                        $isDelete = !$process->isRunning();
                        if ($out = $process->getIncrementalOutput()) {
                            send_shell_output($rocket->model->crontab_id, $out);
                        }
                    } catch (Error|Exception|Throwable $throwable) {
                        $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                        $exception = $throwable->getMessage();
                        $isDelete = true;
                    } finally {
                        $endTime = microtime(true);
                        $duration = $endTime - $startTime;
                        // 删除定时器、停止进程
                        if ($isDelete || Crontab::MAX_EXECUTION_TIME <= $duration) {
                            Timer::del($timer_id);
                            $rocket->stopProcess();
                            CrontabLog::createCrontabLog($rocket->model, $exception ?: '进程运行结束', $code, $duration * 1000);
                        }
                    }
                });
            } catch (Error|Exception|Throwable $throwable) {
                $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                $message = $throwable->getMessage();
                $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                send_shell_output($model->crontab_id, $exception);
            } finally {
                $model->updateRunning($time);
            }
        }, $model->crontab_id);
    }

    /**
     * 【启动计划任务】执行类方法
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function startClassMethod(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return new WorkermanCrontab($model->rule, function () use ($model) {
            $startTime = microtime(true);
            $time = time();
            try {
                AsyncWorker::runClassMethod($model, function (?string $result, Exception|null $e) use ($model, $startTime) {
                    $exception = $e ? $e->getMessage() : ($result ?? '');
                    $code = $e ? ($e->getCode() ?: static::DEFAULT_ERROR_CODE) : 0;
                    $endTime = microtime(true);
                    CrontabLog::createCrontabLog($model, $exception, $code, ($endTime - $startTime) * 1000);
                    send_shell_output($model->crontab_id, $exception);
                });
            } catch (Error|Throwable $throwable) {
                $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                $message = $throwable->getMessage();
                $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                $endTime = microtime(true);
                CrontabLog::createCrontabLog($model, $exception, $code, ($endTime - $startTime) * 1000);
                send_shell_output($model->crontab_id, $exception);
            } finally {
                $model->updateRunning($time);
            }
        }, $model->crontab_id);
    }

    /**
     * 【启动计划任务】访问URL
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function startUrlRequest(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return new WorkermanCrontab($model->rule, function () use ($model) {
            $startTime = microtime(true);
            $time = time();
            $code = 0;
            try {
                $url = $model->target;
                $curl = new Curl();
                $curl->setSslVerify()->get($url);
                if ($curl->isSuccess()) {
                    $response = $curl->response;
                    $exception = is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE);
                    send_shell_output($model->crontab_id, '请求成功，响应：' . $exception);
                } else {
                    $code = $curl->error_code ?: static::DEFAULT_ERROR_CODE;
                    $error_message = is_string($curl->error_message) ? $curl->error_message : '';
                    $exception = "请求失败，状态码：{$code} | 错误消息：{$error_message}";
                    send_shell_output($model->crontab_id, $exception);
                }
            } catch (Error|Throwable $throwable) {
                $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                $message = $throwable->getMessage();
                $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                send_shell_output($model->crontab_id, $exception);
            } finally {
                $endTime = microtime(true);
                CrontabLog::createCrontabLog($model, $exception ?: '进程运行结束', $code, ($endTime - $startTime) * 1000);
                $model->updateRunning($time);
            }
        }, $model->crontab_id);
    }

    /**
     * 【启动计划任务】eval执行PHP代码
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function startEvalCode(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return new WorkermanCrontab($model->rule, function () use ($model) {
            $startTime = microtime(true);
            $time = time();
            $code = 0;
            try {
                $compiled = $model->target;
                ob_start();
                $result = eval($compiled);
                $message = ob_get_clean() ?: '';
                $result = is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE);
                $exception = "任务执行成功，输出：{$message} | 返回值：{$result}";
                send_shell_output($model->crontab_id, $exception);
            } catch (Error|Throwable $throwable) {
                $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                $message = $throwable->getMessage();
                $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                send_shell_output($model->crontab_id, $exception);
            } finally {
                $endTime = microtime(true);
                CrontabLog::createCrontabLog($model, $exception, $code, ($endTime - $startTime) * 1000);
                $model->updateRunning($time);
            }
        }, $model->crontab_id);
    }

    /**
     * 【启动计划任务】shell脚本
     * @param CrontabRocket $rocket
     * @return WorkermanCrontab
     */
    protected function startShellExec(CrontabRocket $rocket): WorkermanCrontab
    {
        $model = $rocket->model;
        return new WorkermanCrontab($model->rule, function () use ($model) {
            $startTime = microtime(true);
            $time = time();
            $code = 0;
            try {
                $compiled = escapeshellcmd($model->target);
                $exception = shell_exec($compiled);
                $exception = is_string($exception) ? $exception : json_encode($exception, JSON_UNESCAPED_UNICODE);
                send_shell_output($model->crontab_id, $exception);
            } catch (Error|Throwable $throwable) {
                $code = $throwable->getCode() ?: static::DEFAULT_ERROR_CODE;
                $message = $throwable->getMessage();
                $exception = "任务执行异常，异常码：{$code} | 异常消息：{$message}";
                send_shell_output($model->crontab_id, $exception);
            } finally {
                $endTime = microtime(true);
                CrontabLog::createCrontabLog($model, $exception, $code, ($endTime - $startTime) * 1000);
                $model->updateRunning($time);
            }
        }, $model->crontab_id);
    }
}
