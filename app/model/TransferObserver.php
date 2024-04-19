<?php

namespace app\model;

/**
 * 模型观察者：cn_transfer
 * @usage Transfer::observe(TransferObserver::class);
 */
class TransferObserver
{
   /**
     * 监听数据即将创建的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function creating(Transfer $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function created(Transfer $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function updating(Transfer $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function updated(Transfer $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function saving(Transfer $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function saved(Transfer $model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function deleting(Transfer $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function deleted(Transfer $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function restoring(Transfer $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Transfer $model
     * @return void
     */
    public function restored(Transfer $model): void
    {
    } 
}
