<?php

namespace plugin\cron\api;

use plugin\cron\app\interfaces\CrontabLayuiTemplateInterface;
use plugin\cron\app\interfaces\CrontabSchedulerInterface;
use plugin\cron\app\interfaces\CrontabTaskTypeEnumsInterface;
use support\Container;

/**
 * 扩展支持更多的计划任务类型
 */
class CrontabExtend
{
    /**
     * @var array<CrontabTaskTypeEnumsInterface>
     */
    protected array $typeEnums = [];

    /**
     * @var array<CrontabLayuiTemplateInterface>
     */
    protected array $templates = [];

    /**
     * @var array<CrontabSchedulerInterface>
     */
    protected array $schedulers = [];

    /**
     * 单例模式
     * @return static
     */
    public static function getInstance(): static
    {
        return Container::get(static::class);
    }

    /**
     * @return array<CrontabTaskTypeEnumsInterface>
     */
    public function getTypeEnums(): array
    {
        return $this->typeEnums;
    }

    /**
     * @return array<CrontabLayuiTemplateInterface>
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @return array<CrontabSchedulerInterface>
     */
    public function getSchedulers(): array
    {
        return $this->schedulers;
    }

    /**
     * 注册计划任务类型枚举
     * @param CrontabTaskTypeEnumsInterface $typeEnum
     * @return CrontabExtend
     */
    public function registerEnumsProvider(CrontabTaskTypeEnumsInterface $typeEnum): static
    {
        $this->typeEnums[] = $typeEnum;
        return $this;
    }

    /**
     * 注册计划任务配置模板
     * @param CrontabLayuiTemplateInterface $layuiTemplate
     * @return CrontabExtend
     */
    public function registerTemplateProvider(CrontabLayuiTemplateInterface $layuiTemplate): static
    {
        $this->templates[] = $layuiTemplate;
        return $this;
    }

    /**
     * 注册计划任务启动器
     * @param CrontabSchedulerInterface $scheduler
     * @return CrontabExtend
     */
    public function registerSchedulerProvider(CrontabSchedulerInterface $scheduler): static
    {
        $this->schedulers[] = $scheduler;
        return $this;
    }
}
