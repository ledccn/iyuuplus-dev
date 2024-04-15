<?php

namespace plugin\cron\app\services;

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
     * 计划任务对象
     * @var WorkermanCrontab
     */
    private WorkermanCrontab $crontab;

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
        return $this->crontab;
    }

    /**
     * 设置计划任务对象
     * @param WorkermanCrontab $crontab
     * @return CrontabRocket
     */
    public function setCrontab(WorkermanCrontab $crontab): static
    {
        $this->crontab = $crontab;
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
}
