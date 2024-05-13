<?php

namespace app\model;

use app\admin\services\client\ClientServices;
use app\common\HasStaticBackup;
use InvalidArgumentException;
use Iyuu\BittorrentClient\ClientEnums;
use RuntimeException;

/**
 * 模型观察者：cn_client 下载器
 * @usage Client::observe(ClientObserver::class);
 */
class ClientObserver
{
    use HasStaticBackup;

    /**
     * 监听数据即将创建的事件。
     *
     * @param Client $model
     * @return void
     */
    public function creating(Client $model): void
    {
        $model->brand = ClientEnums::from($model->brand)->value;
        // 设置为默认下载器
        if ($model->is_default) {
            if (empty($model->enabled)) {
                throw new InvalidArgumentException('默认下载器必须启用');
            }
            Client::cancelDefault($model);
        } else {
            // 创建第一个自动设置为默认
            if (!Client::where('is_default', '=', 1)->exists()) {
                $model->is_default = 1;
            }
        }
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param Client $model
     * @return void
     */
    public function created(Client $model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Client $model
     * @return void
     */
    public function updating(Client $model): void
    {
        $dirty = $model->getDirty();
        $readonly = ['brand', 'created_at'];
        foreach ($readonly as $field) {
            if (array_key_exists($field, $dirty)) {
                throw new RuntimeException("禁止修改只读字段{$field}");
            }
        }

        // 设置为默认下载器
        if (array_key_exists('is_default', $dirty)) {
            if ($model->is_default) {
                if (empty($model->enabled)) {
                    throw new InvalidArgumentException('请启用下载器，再设置为默认');
                }
                Client::cancelDefault($model);
            }
        }

        // 设置为禁用
        if (array_key_exists('enabled', $dirty)) {
            if (empty($model->enabled) && $model->is_default) {
                throw new InvalidArgumentException('默认下载器必须启用');
            }
        }
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Client $model
     * @return void
     */
    public function updated(Client $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Client $model
     * @return void
     */
    public function saving(Client $model): void
    {
        $model->hostname = rtrim($model->hostname, '/');
        ClientServices::testBittorrent($model)->status();
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param Client $model
     * @return void
     */
    public function saved(Client $model): void
    {
        static::onBackupByModel($model);
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param Client $model
     * @return void
     */
    public function deleting(Client $model): void
    {
        if ($model->is_default) {
            throw new InvalidArgumentException('禁止删除默认下载器');
        }
        Reseed::deleteByClientId($model->id);
        Transfer::deleteByFromClientId($model->id);
        Transfer::deleteByToClientId($model->id);
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param Client $model
     * @return void
     */
    public function deleted(Client $model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param Client $model
     * @return void
     */
    public function restoring(Client $model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param Client $model
     * @return void
     */
    public function restored(Client $model): void
    {
    }
}
