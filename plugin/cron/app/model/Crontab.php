<?php

namespace plugin\cron\app\model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use plugin\admin\app\model\Base;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

/**
 * 计划任务
 * @property int $crontab_id 主键
 * @property string $title 任务标题
 * @property int|string $task_type 任务类型
 * @property string $crontab 执行周期
 * @property string $rule 执行表达式
 * @property string $target 调用字符串
 * @property string|array $parameter 调用参数
 * @property int $running_count 已运行次数
 * @property int $last_running_time 上次运行时间
 * @property int $sort 排序，越大越前
 * @property int $record_log 是否记录日志
 * @property int $enabled 启用
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class Crontab extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cn_crontab';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'crontab_id';

    /**
     * linux系统的crontab任务永远在第1秒执行,且添加定时任务后的1分钟之内是不会执行该任务(即使语法上完全满足)
     * @var string
     */
    const cron_format = '%s %s %s %s %s';

    /**
     * where可能的值
     */
    const cron_where = [
        'day', 'day_n', 'hour', 'hour_n', 'minute', 'minute_n', 'second', 'second_n', 'week', 'month'
    ];

    /**
     * 类型转换。
     * @var array
     */
    protected $casts = [
        'crontab' => AsArrayObject::class,
    ];

    /**
     * 获取所有启用的计划任务
     * @return Collection|Builder
     */
    public static function allEnabledCrontab(): Collection|Builder
    {
        return static::where('enabled', 1)->orderByDesc('sort')->get();
    }

    /**
     * 转换为Linux的Crontab语法
     * @param array $params 执行周期
     * array(
     *      'where' => ''
     *      'weeks' => ''
     *      'day' => ''
     *      'date' => ''
     *      'hour' => ''
     *      'minute' => ''
     * )
     * @return string
     *   0    1    2    3    4    5
     *   *    *    *    *    *    *
     *   -    -    -    -    -    -
     *   |    |    |    |    |    |
     *   |    |    |    |    |    +----- 星期day of week (0 - 6) (Sunday=0)
     *   |    |    |    |    +----- 月month (1 - 12)
     *   |    |    |    +------- 日day of month (1 - 31)
     *   |    |    +--------- 时hour (0 - 23)
     *   |    +----------- 分min (0 - 59)
     *   +------------- 秒sec (0-59)
     */
    public static function parseCrontab(array $params): string
    {
        $cron = '';
        $where = $params['where'] ?? null;  //条件
        $weeks = $params['weeks'] ?? null;  //星期
        $day = $params['day'] ?? null;      //天
        $date = $params['date'] ?? null;    //日期
        $hour = $params['hour'] ?? null;    //时
        $minute = $params['minute'] ?? null;    //分
        $second = $params['second'] ?? '*';     //秒
        if ($where === null || !in_array($where, self::cron_where)) {
            throw new InvalidArgumentException('执行周期where字段的值无效');
        }

        switch ($where) {
            case 'day':         //每天
                $cron = sprintf(self::cron_format, $minute, $hour, '*', '*', '*');
                break;
            case 'day_n':       //N天
                $cron = sprintf(self::cron_format, $minute, $hour, '*/' . $day, '*', '*');
                break;
            case 'hour':        //每小时
                $cron = sprintf(self::cron_format, $minute, '*', '*', '*', '*');
                break;
            case 'hour_n':      //N小时
                $cron = sprintf(self::cron_format, $minute, '*/' . $hour, '*', '*', '*');
                break;
            case 'minute':      //每分钟
                $cron = sprintf(self::cron_format, '*', '*', '*', '*', '*');
                break;
            case 'minute_n':    //N分钟
                $cron = sprintf(self::cron_format, '*/' . $minute, '*', '*', '*', '*');
                break;
            case 'second':      //每秒
                $cron = sprintf(self::cron_format, '*', '*', '*', '*', '*', '*');
                break;
            case 'second_n':    //N秒
                $cron = sprintf(self::cron_format, '*/' . $second, '*', '*', '*', '*', '*');
                break;
            case 'week':        //每周
                $cron = sprintf(self::cron_format, $minute, $hour, '*', '*', $weeks);
                break;
            case 'month':       //每月
                $cron = sprintf(self::cron_format, $minute, $hour, '*', $date, '*');
                break;
        }

        return $cron;
    }

    /**
     * 更新运行次数/时间
     * @param int $time
     * @return $this
     */
    public function updateRunning(int $time): static
    {
        $this->running_count += 1;
        $this->last_running_time = $time;
        $this->save();
        return $this;
    }

    /**
     * 【立刻运行】webman命令
     * @param Crontab $model
     * @param callable|null $callback 当STDOUT或STDERR上有输出时运行，原型为$callback($type, $buffer)
     * @return Process
     */
    public static function runWebmanCommand(Crontab $model, ?callable $callback = null): Process
    {
        $command = [PHP_BINARY, base_path('webman'), $model->target];
        if ($parameter = $model->parameter ?: '') {
            foreach (explode(' ', $parameter) as $argument) {
                if (strlen(trim($argument))) {
                    $command[] = trim($argument);
                }
            }
        }

        $process = new Process($command, base_path());
        $process->start($callback);
        return $process;
    }

    /**
     * 【立刻运行】php源代码
     * @param Crontab $model
     * @param callable|null $callback 当STDOUT或STDERR上有输出时运行，原型为$callback($type, $buffer)
     * @return Process
     */
    public static function runPhpCode(Crontab $model, ?callable $callback = null): Process
    {
        $script = $model->target;

        $process = new PhpProcess($script, base_path());
        $process->start($callback);
        return $process;
    }

    /**
     * 【立刻运行】shell脚本
     * @param Crontab $model
     * @param callable|null $callback 当STDOUT或STDERR上有输出时运行，原型为$callback($type, $buffer)
     * @return Process
     */
    public static function runShellExec(Crontab $model, ?callable $callback = null): Process
    {
        $script = $model->target;

        $process = Process::fromShellCommandline($script);
        $process->start($callback);
        return $process;
    }
}
