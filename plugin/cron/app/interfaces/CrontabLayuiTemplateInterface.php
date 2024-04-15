<?php

namespace plugin\cron\app\interfaces;

use Ledc\Element\GenerateInterface;

/**
 * 第二步：生成Layui计划任务配置模板
 */
interface CrontabLayuiTemplateInterface
{
    /**
     * 生成Layui计划任务配置模板
     * @param int $type 任务类型
     * @return GenerateInterface|null
     */
    public function generate(int $type): ?GenerateInterface;
}
