<?php

namespace plugin\cron\app\model;

use Illuminate\Database\Eloquent\Casts\ArrayObject;
use InvalidArgumentException;
use plugin\cron\app\services\CrontabEventEnums;

/**
 * 模型观察者：cn_crontab
 * @usage Crontab::observe(CrontabObserver::class);
 */
class CrontabObserver
{
    /**
     * 保存事件到目录内
     * @param int $crontab_id
     * @param string $event
     * @return void
     */
    public static function saveEventToDir(int $crontab_id, string $event): void
    {
        clearstatcache();
        $directory = config('crontab.observer_directory');
        $filename = $directory . DIRECTORY_SEPARATOR . $crontab_id . '.' . $event;
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            throw new InvalidArgumentException(sprintf('Crontab observer directory does not exist and cannot be created: "%s".', $directory));
        }

        file_put_contents($filename, time());
    }

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
        self::saveEventToDir($model->crontab_id, CrontabEventEnums::created->name);
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updating(Crontab $model): void
    {
        unset($model->task_type);
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updated(Crontab $model): void
    {
        $dirty = $model->getDirty();
        foreach (['title', 'rule', 'target', 'parameter', 'record_log', 'enabled'] as $key) {
            if (array_key_exists($key, $dirty)) {
                self::saveEventToDir($model->crontab_id, CrontabEventEnums::updated->name);
            }
        }
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function saving(Crontab $model): void
    {
        if ($crontab = $model->crontab) {
            $cron = is_string($crontab) ? json_decode($crontab, true) : $crontab;
            $model->rule = Crontab::parseCrontab($cron instanceof ArrayObject ? $cron->toArray() : $cron);
        }

        if ($parameter = $model->parameter) {
            if (is_array($parameter) || is_object($parameter)) {
                $model->parameter = json_encode($parameter, JSON_UNESCAPED_UNICODE);
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
        self::saveEventToDir($model->crontab_id, CrontabEventEnums::deleted->name);
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
