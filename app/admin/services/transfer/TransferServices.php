<?php

namespace app\admin\services\transfer;

use plugin\cron\app\model\Crontab;

/**
 * 自动转移做种客户端服务类
 */
class TransferServices
{
    /**
     * 计划任务：数据模型
     * @var Crontab
     */
    protected Crontab $crontabModel;

    /**
     * @param int $crontab_id
     */
    public function __construct(public readonly int $crontab_id)
    {

    }

    /**
     * 执行逻辑
     * @return void
     */
    public function run(): void
    {}
}
