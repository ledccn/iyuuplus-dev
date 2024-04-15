## 详细介绍

异步高性能计划任务，仿制宝塔面板，简单易用；底层基于webman/admin、workerman/crontab

## 功能特性

- 生成crontab表达式
- 异步非阻塞
- 实时终端

## 安装使用

```sh
composer require workerman/crontab
composer require webman/admin
composer require webman/push
composer require ledc/curl
composer require ledc/element
composer require symfony/process
```

## 扩展支持更多计划任务类型

### 示例

自定义Bootstrap的start方法内，注册扩展的计划任务类型

```php
use plugin\cron\api\CrontabExtend;
use plugin\cron\app\interfaces\CrontabLayuiTemplateInterface;
use plugin\cron\app\interfaces\CrontabSchedulerInterface;
use plugin\cron\app\interfaces\CrontabTaskTypeEnumsInterface;

// 示例：扩展支持一个新的计划任务类型
CrontabExtend::getInstance()->registerEnumsProvider(new class implements CrontabTaskTypeEnumsInterface {
    public static function select(): array
    {
        return [
            '自动下载' => 50,
            '自动转移' => 51,
        ];
    }
})->registerTemplateProvider(new class implements CrontabLayuiTemplateInterface {
    public function generate(int $type): ?GenerateInterface
    {
        return match ($type) {
            50, 51 => new Concrete(),
            default => null,
        };
    }
})->registerSchedulerProvider(new class implements CrontabSchedulerInterface {
    public function start(CrontabRocket $rocket): ?Crontab
    {
        $model = $rocket->model;
        return match ((int)$model->task_type) {
            50, 51 => new Crontab($model->rule, function () use ($rocket) {
                $exception = '任务执行成功，任务ID：' . $rocket->model->crontab_id . PHP_EOL;
                send_shell_output($rocket->model->crontab_id, $exception);
                $rocket->model->updateRunning(time());
            }),
            default => null,
        };
    }
});
```

## 联系方式

QQ群：630560602