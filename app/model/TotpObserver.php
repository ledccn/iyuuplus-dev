<?php

namespace app\model;

/**
 * 模型观察者：cn_totp
 * @usage Totp::observe(TotpObserver::class);
 */
class TotpObserver
{
    /**
     * 监听数据即将创建的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function creating(Totp $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function created(Totp $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function updating(Totp $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function updated(Totp $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function saving(Totp $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function saved(Totp $model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function deleting(Totp $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function deleted(Totp $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function restoring(Totp $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Totp $model
     * @return void
     */
    public function restored(Totp $model): void
    {
    }
}
