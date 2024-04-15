<?php

namespace plugin\cron\app\model;

use plugin\admin\app\model\Base;
use support\Log;
use Throwable;

/**
 * 日志
 * @property integer $id 主键(主键)
 * @property integer $crontab_id 任务id
 * @property string $rule 执行表达式
 * @property string $target 调用任务字符串
 * @property string $parameter 任务调用参数
 * @property string $exception 异常信息
 * @property integer $return_code 执行状态：0成功
 * @property integer $running_time 执行耗时毫秒
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class CrontabLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_crontab_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 创建计划任务日志
     * @param Crontab $crontab
     * @param string $exception 异常信息
     * @param int $code 执行状态：0成功
     * @param int|float $running_time 执行耗时毫秒
     * @return static|null
     */
    public static function createCrontabLog(Crontab $crontab, string $exception = '', int $code = 0, int|float $running_time = 0): ?static
    {
        try {
            if ($crontab->record_log) {
                $model = new static();
                $model->crontab_id = $crontab->crontab_id;
                $model->target = $crontab->target;
                $model->parameter = $crontab->parameter;
                $model->exception = $exception;
                $model->return_code = $code;
                $model->running_time = (int)$running_time;
                $model->save();

                return $model;
            }
        } catch (Throwable $throwable) {
            Log::error('创建计划任务日志异常：' . $throwable->getMessage());
        }

        return null;
    }
}
