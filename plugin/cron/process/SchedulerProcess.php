<?php

namespace plugin\cron\process;

use Error;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;
use plugin\cron\api\Install;
use plugin\cron\app\model\Crontab;
use plugin\cron\app\services\CrontabEventEnums;
use plugin\cron\app\services\CrontabRocket;
use plugin\cron\app\services\Scheduler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use support\Container;
use support\Log;
use Throwable;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 计划任务调度进程
 */
class SchedulerProcess
{
    /**
     * @var Worker
     */
    protected static Worker $worker;

    /**
     * 计划任务池
     * @var array<int, CrontabRocket>
     */
    protected static array $pools = [];

    /**
     * 模型观察者目录
     * @var string
     */
    protected readonly string $observerDirectory;

    /**
     * 构造函数
     * @param string $secret 通信密钥
     */
    public function __construct(public readonly string $secret)
    {
        if (!Install::isInstalled()) {
            return;
        }
        $directory = config('crontab.observer_directory');
        if (empty($directory)) {
            throw new InvalidArgumentException('Crontab observer directory does not config.');
        }
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            throw new InvalidArgumentException(sprintf('Crontab observer directory does not exist and cannot be created: "%s".', $directory));
        }
        $this->observerDirectory = $directory;
        Timer::add(1, [$this, 'scanObserverDirectory']);
    }

    /**
     * 扫描观察者目录
     * @return void
     */
    public function scanObserverDirectory(): void
    {
        clearstatcache();
        $dirIterator = new RecursiveDirectoryIterator($this->observerDirectory, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS);
        $iterator = new RecursiveIteratorIterator($dirIterator);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (is_dir($file->getRealPath())) {
                continue;
            }
            if (!in_array($file->getExtension(), CrontabEventEnums::values(), true)) {
                continue;
            }

            try {
                [$crontab_id, $event] = explode('.', $file->getFilename());
                if (empty($crontab_id)) {
                    continue;
                }

                if (unlink($file->getPathname())) {
                    switch ($event) {
                        case CrontabEventEnums::created->name:
                            static::enableCrontab(Crontab::find($crontab_id));
                            break;
                        case CrontabEventEnums::updated->name:
                            $model = Crontab::find($crontab_id);
                            static::disableCrontab($model);
                            static::enableCrontab($model);
                            break;
                        case CrontabEventEnums::deleted->name:
                            static::deleteCrontab($crontab_id);
                            break;
                        case CrontabEventEnums::start->name:
                            $model = Crontab::find($crontab_id);
                            static::startCrontabProcess($model);
                            break;
                        case CrontabEventEnums::stop->name:
                            $model = Crontab::find($crontab_id);
                            static::stopCrontabProcess($model);
                            break;
                        default:
                            // 其他未处理的事件
                            break;
                    }
                } else {
                    throw new Exception('Remove crontab event file fail.');
                }
            } catch (Error|Exception|Throwable $throwable) {
                Log::error(sprintf('扫描观察者目录失败，文件名：%s | 错误码：%d | 错误消息：%s',
                    $file->getFilename(),
                    $throwable->getCode(),
                    $throwable->getMessage()));
            }
        }
    }

    /**
     * 子进程启动时的回调函数
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        static::$worker = $worker;
        if (!Install::isInstalled()) {
            return;
        }

        $this->initPoolsCrontab();
    }

    /**
     * 初始化计划任务池
     * @return void
     */
    public function initPoolsCrontab(): void
    {
        $list = Crontab::allEnabledCrontab();
        if (!$list->isEmpty()) {
            $list->each(function (Crontab $model, $key) {
                try {
                    static::enableCrontab($model);
                } catch (Error|Throwable $throwable) {
                    Log::error(sprintf('初始化计划任务失败，任务ID：%d | 标题：%s | 错误码：%d | 错误消息：%s',
                        $model->crontab_id,
                        $model->title,
                        $throwable->getCode(),
                        $throwable->getMessage()));
                }
            });
        }
    }

    /**
     * 获取全部计划任务池
     * @return array<int, CrontabRocket>
     */
    public static function getPools(): array
    {
        return self::$pools;
    }

    /**
     * 获取计划任务小火箭
     * @param int $crontab_id 计划任务ID
     * @return CrontabRocket|null
     */
    public static function getPoolByCrontabId(int $crontab_id): ?CrontabRocket
    {
        return self::$pools[$crontab_id] ?? null;
    }

    /**
     * 设置计划任务小火箭
     * @param int $crontab_id
     * @param CrontabRocket $rocket
     */
    public static function setPool(int $crontab_id, CrontabRocket $rocket): void
    {
        static::$pools[$crontab_id] = $rocket;
    }

    /**
     * 启用指定的计划任务
     * @param Crontab $model
     * @return bool
     */
    public static function enableCrontab(Crontab $model): bool
    {
        $crontab_id = $model->crontab_id;
        if (static::getPoolByCrontabId($crontab_id)) {
            return true;
        }
        if (empty($model->enabled)) {
            return false;
        }

        /** @var Scheduler $services */
        $services = Container::get(Scheduler::class);
        $rocket = new CrontabRocket($model);
        $rocket->setCrontab($services->start($rocket));
        static::setPool($crontab_id, $rocket);
        echo 'Success 载入计划任务，ID ' . $model->crontab_id . ' | 标题：' . $model->title . PHP_EOL;
        return true;
    }

    /**
     * 禁用指定的计划任务
     * @param Crontab $model
     * @return bool
     */
    public static function disableCrontab(Crontab $model): bool
    {
        return static::deleteCrontab($model->crontab_id);
    }

    /**
     * 删除指定的计划任务
     * @param int $crontab_id
     * @return bool
     */
    public static function deleteCrontab(int $crontab_id): bool
    {
        if ($crontabRocket = static::getPoolByCrontabId($crontab_id)) {
            $crontabRocket->getCrontab()->destroy();
            $crontabRocket->getProcess()?->stop(2);
            unset(static::$pools[$crontab_id]);
            echo 'Success 删除计划任务，ID ' . $crontab_id . PHP_EOL;
            return true;
        } else {
            echo 'Success 删除计划任务（空的）' . PHP_EOL;
            return false;
        }
    }

    /**
     * 运行计划任务子进程
     * @param Crontab $model
     * @return bool
     */
    public static function startCrontabProcess(Crontab $model): bool
    {
        $crontab_id = $model->crontab_id;
        if ($crontabRocket = static::getPoolByCrontabId($crontab_id)) {
            $cb = $crontabRocket->getCrontab()->getCallback();
            $process = $crontabRocket->getProcess();
            if (!$process || !$process->isRunning()) {
                call_user_func($cb);
            }
            echo 'Success 手动运行 计划任务，ID ' . $crontab_id . PHP_EOL;
            return true;
        } else {
            echo 'Success 手动运行 计划任务（空的）' . PHP_EOL;
            return false;
        }
    }

    /**
     * 停止计划任务子进程
     * @param Crontab $model
     * @return bool
     */
    public static function stopCrontabProcess(Crontab $model): bool
    {
        $crontab_id = $model->crontab_id;
        if ($crontabRocket = static::getPoolByCrontabId($crontab_id)) {
            $crontabRocket->getProcess()?->stop(3);
            echo 'Success 手动停止 计划任务，ID ' . $crontab_id . PHP_EOL;
            return true;
        }
        echo 'Success 手动停止 计划任务（空的）' . PHP_EOL;
        return false;
    }

    /**
     * 子进程退出时的回调函数
     * @return void
     */
    public function onWorkerStop(): void
    {
        foreach (array_keys(self::$pools) as $crontab_id) {
            static::deleteCrontab($crontab_id);
        }
    }

    /**
     * 设置Worker收到reload信号后执行的回调
     * @return void
     */
    public function onWorkerReload()
    {
    }
}
