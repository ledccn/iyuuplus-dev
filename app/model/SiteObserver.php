<?php

namespace app\model;

use app\common\HasStaticBackup;
use RuntimeException;

/**
 * 模型观察者：cn_sites
 * @usage Site::observe(SiteObserver::class);
 */
class SiteObserver
{
    use HasStaticBackup;

    /**
     * 监听数据即将创建的事件。
     *
     * @param Site $model
     * @return void
     */
    public function creating(Site $model): void
    {
        $model->disabled = 1;
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Site $model
     * @return void
     */
    public function created(Site $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Site $model
     * @return void
     */
    public function updating(Site $model): void
    {
        $dirty = $model->getDirty();
        $readonly = ['sid', 'site'];
        foreach ($readonly as $field) {
            if (array_key_exists($field, $dirty)) {
                throw new RuntimeException("禁止修改只读字段{$field}");
            }
        }
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Site $model
     * @return void
     */
    public function updated(Site $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Site $model
     * @return void
     */
    public function saving(Site $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Site $model
     * @return void
     */
    public function saved(Site $model): void
    {
        static::onBackupByModel($model);
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Site $model
     * @return void
     */
    public function deleting(Site $model): void
    {
        Reseed::deleteBySid($model->sid);
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Site $model
     * @return void
     */
    public function deleted(Site $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Site $model
     * @return void
     */
    public function restoring(Site $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Site $model
     * @return void
     */
    public function restored(Site $model): void
    {
    }
}
