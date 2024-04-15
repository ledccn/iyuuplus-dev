<?php

namespace plugin\cron\app\model;

/**
 * 模型观察者：cn_crontab_log
 * @usage CrontabLog::observe(CrontabLogObserver::class);
 */
class CrontabLogObserver
{
    /**
     * 监听数据即将创建的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function creating(CrontabLog $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function created(CrontabLog $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function updating(CrontabLog $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function updated(CrontabLog $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function saving(CrontabLog $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function saved(CrontabLog $model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function deleting(CrontabLog $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function deleted(CrontabLog $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function restoring(CrontabLog $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param CrontabLog $model
     * @return void
     */
    public function restored(CrontabLog $model): void
    {
    }
}
