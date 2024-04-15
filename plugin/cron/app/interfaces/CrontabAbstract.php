<?php

namespace plugin\cron\app\interfaces;

use Ledc\Element\GenerateInterface;

/**
 * 计划任务抽象类
 */
abstract class CrontabAbstract implements CrontabTaskTypeEnumsInterface, CrontabLayuiTemplateInterface, CrontabSchedulerInterface, GenerateInterface
{
}
