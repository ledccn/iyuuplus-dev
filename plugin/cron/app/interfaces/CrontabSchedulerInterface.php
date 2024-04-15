<?php

namespace plugin\cron\app\interfaces;

use plugin\cron\app\services\CrontabRocket;
use Workerman\Crontab\Crontab;

/**
 * 第三步：启动计划任务
 */
interface CrontabSchedulerInterface
{
    /**
     * 启动当前计划任务
     * @param CrontabRocket $rocket
     * @return Crontab|null
     */
    public function start(CrontabRocket $rocket): ?Crontab;
}
