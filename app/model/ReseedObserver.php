<?php

namespace app\model;

use app\admin\support\NotifyAdmin;
use app\model\enums\ReseedStatusEnums;

/**
 * 模型观察者：cn_reseed自动辅种
 * @usage Reseed::observe(ReseedObserver::class);
 */
class ReseedObserver
{
    /**
     * 监听数据即将创建的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function creating(Reseed $model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function created(Reseed $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function updating(Reseed $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function updated(Reseed $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function saving(Reseed $model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function saved(Reseed $model): void
    {
        $dirty = $model->getDirty();
        if (array_key_exists('status', $dirty)) {
            $statusEnums = ReseedStatusEnums::tryFrom($model->status);
            $msg = "客户端:{$model->client_id} 站点:{$model->site} 种子:{$model->torrent_id}";
            match ($statusEnums) {
                ReseedStatusEnums::Success => NotifyAdmin::success($msg),
                ReseedStatusEnums::Fail => NotifyAdmin::error($msg),
                default => null,
            };
        }
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function deleting(Reseed $model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function deleted(Reseed $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function restoring(Reseed $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Reseed $model
     * @return void
     */
    public function restored(Reseed $model): void
    {
    }
}
