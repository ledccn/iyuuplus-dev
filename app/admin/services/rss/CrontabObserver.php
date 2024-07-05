<?php

namespace app\admin\services\rss;

use app\command\RssCommand;
use app\common\Number;
use app\model\enums\SizeUnitEnums;
use InvalidArgumentException;
use plugin\cron\app\model\Crontab;

/**
 * 模型观察者：cn_crontab
 * @usage Crontab::observe(CrontabObserver::class);
 */
class CrontabObserver
{
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
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updating(Crontab $model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function updated(Crontab $model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param Crontab $model
     * @return void
     */
    public function saving(Crontab $model): void
    {
        $task_type = $model->task_type;
        if (RssSelectEnums::rss->value === (int)$task_type) {
            $model->target = RssCommand::COMMAND_NAME;
            $parameter = $model->parameter;
            if (empty($parameter)) {
                throw new InvalidArgumentException('RSS地址、下载器、标记规则必填');
            }

            $parameter = is_array($parameter) ? $parameter : json_decode($parameter, true);

            if (!empty($parameter['size_min'])) {
                if (!ctype_digit((string)$parameter['size_min'])) {
                    throw new InvalidArgumentException('种子大小最小值只能为整数');
                }
            }
            if (!empty($parameter['size_max'])) {
                if (!ctype_digit((string)$parameter['size_max'])) {
                    throw new InvalidArgumentException('种子大小最大值只能为整数');
                }
            }

            // 验证种子大小的最小值、最大值
            if (!empty($parameter['size_min']) && !empty($parameter['size_max'])) {
                $size_min = SizeUnitEnums::convert($parameter['size_min'], SizeUnitEnums::from($parameter['size_min_unit']));
                $size_max = SizeUnitEnums::convert($parameter['size_max'], SizeUnitEnums::from($parameter['size_max_unit']));
                if (-1 !== Number::bccomp($size_min, $size_max)) {
                    throw new InvalidArgumentException('种子大小的最小值必须小于最大值');
                }
            }

            // 验证简易模式规则
            $text_selector = $parameter['text_selector'] ?? '';
            $text_filter = $parameter['text_filter'] ?? '';
            if ($text_selector && $text_filter) {
                $intersect = array_intersect(array_map('strtolower', explode(',', $text_selector)), array_map('strtolower', explode(',', $text_filter)));
                if (!empty($intersect)) {
                    throw new InvalidArgumentException('包含关键字、排除关键字存在交集：' . implode(',', $intersect));
                }
            }

            // 验证正则模式规则
            // todo... 验证正则表达式的格式是否正确
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
