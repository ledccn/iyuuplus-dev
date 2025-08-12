<?php

namespace plugin\cron\app\services;

use LogicException;
use plugin\cron\app\model\Crontab;
use Symfony\Component\Process\Process;
use Workerman\Crontab\Crontab as WorkermanCrontab;

/**
 * 计划任务小火箭
 */
class CrontabRocket
{
    /**
     * 子进程句柄
     * @var Process|null
     */
    private ?Process $process = null;
    /**
     * workerman的Crontab对象任务id
     * @var int
     */
    private int $workermanCrontabId = 0;

    /**
     * 构造函数
     * @param Crontab $model 计划任务数据模型
     */
    public function __construct(public readonly Crontab $model)
    {
    }

    /**
     * 获取计划任务对象
     * @return WorkermanCrontab
     */
    public function getCrontab(): WorkermanCrontab
    {
        if ($this->workermanCrontabId <= 0) {
            throw new LogicException('调用时机错误，请联系开发者');
        }

        $crontab = WorkermanCrontab::getAll()[$this->workermanCrontabId] ?? null;
        if (!$crontab) {
            throw new LogicException('WorkermanCrontab对象不存在，请联系开发者');
        }
        return $crontab;
    }

    /**
     * 设置计划任务对象
     * @param WorkermanCrontab $crontab
     * @return CrontabRocket
     */
    public function setCrontab(WorkermanCrontab $crontab): static
    {
        $this->workermanCrontabId = $crontab->getId();
        return $this;
    }

    /**
     * 获取进程句柄
     * @return Process|null
     */
    public function getProcess(): ?Process
    {
        return $this->process;
    }

    /**
     * 设置进程句柄
     * @param ?Process $process
     * @return CrontabRocket
     */
    public function setProcess(?Process $process): static
    {
        $this->process = $process;
        return $this;
    }

    /**
     * 停止进程
     * @return void
     */
    public function stopProcess(): void
    {
        $this->process?->stop(2);
        $this->process = null;
    }
}
