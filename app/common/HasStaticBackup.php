<?php

namespace app\common;

use Illuminate\Database\Eloquent\Collection;
use support\Model;

/**
 * 备份模型数据
 */
trait HasStaticBackup
{
    /**
     * 备份模型
     * @param Model $model
     * @return void
     */
    public static function onBackupByModel(Model $model): void
    {
        /** @var Collection $list */
        $list = $model::get();
        if ($list->isNotEmpty()) {
            $dir = runtime_path('backup');
            $name = $model->getTable() . '.json';
            $filename = $dir . DIRECTORY_SEPARATOR . $name;
            file_put_contents($filename, json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
        }
    }
}
