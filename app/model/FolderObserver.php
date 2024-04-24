<?php

namespace app\model;

use app\common\HasStaticBackup;

/**
 * 数据目录 模型观察者：cn_folder
 * @usage Folder::observe(FolderObserver::class);
 */
class FolderObserver
{
    use HasStaticBackup;

    /**
     * 监听数据即将创建的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function creating(Folder $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function created(Folder $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function updating(Folder $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function updated(Folder $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function saving(Folder $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function saved(Folder $model): void
    {
        static::onBackupByModel($model);
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function deleting(Folder $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function deleted(Folder $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function restoring(Folder $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Folder $model
     * @return void
     */
    public function restored(Folder $model): void
    {
    }
}
