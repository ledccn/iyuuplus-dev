<?php

namespace app\admin\services\reseed;

use app\command\ReseedCommand;
use InvalidArgumentException;
use plugin\cron\app\model\Crontab;

/**
 * 模型观察者：cn_crontab
 * @usage Crontab::observe(CrontabObserver::class);
 */
class CrontabObserver
{
    /**
     * 监听数据即将创建的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function creating(Crontab $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function created(Crontab $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updating(Crontab $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updated(Crontab $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function saving(Crontab $model): void
    {
        $task_type = $model->task_type;
        if (ReseedSelectEnums::reseed->value === (int)$task_type) {
            $model->target = ReseedCommand::COMMAND_NAME;
            $parameter = $model->parameter;
            if (empty($parameter)) {
                throw new InvalidArgumentException('辅种站点、辅种下载器必填');
            }

            $parameter = is_array($parameter) ? $parameter : json_decode($parameter, true);
            if (empty($parameter['sites'])) {
                throw new InvalidArgumentException('辅种站点必填');
            }
            if (empty($parameter['clients'])) {
                throw new InvalidArgumentException('辅种下载器必填');
            }
        }
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function saved(Crontab $model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function deleting(Crontab $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function deleted(Crontab $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function restoring(Crontab $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function restored(Crontab $model): void
    {
    }
}
