<?php

namespace app\admin\services;

use app\admin\support\NotifyAdmin;
use Iyuu\SiteManager\Spider\Params;
use RuntimeException;
use Symfony\Component\Process\Process;
use Workerman\Timer;

/**
 * IYUU更新类
 */
class SystemServices
{

    /**
     * 拉取最新项目
     * @return array
     */
    public static function gitPull(): array
    {
        $command = DIRECTORY_SEPARATOR === '\\' ? ['git', 'pull'] : ['sh', base_path('gg.sh')];

        $process = new Process($command, base_path(), null, null, 30);
        $process->run();

        $status = $process->getExitCode();
        $output = $process->getOutput();

        if ($status) {
            throw new RuntimeException('刷新失败：' . json_encode($output, JSON_UNESCAPED_UNICODE));
        }

        return [
            'status' => $status,
            'output' => $output,
        ];
    }

    /**
     *  - 启动、重启、停止、重载等webman支持的命令
     * @param string $command
     * @return array
     */
    public static function gitaction(string $command): array
    {
        if (!in_array($command, Params::ACTION_LIST, true)) {
            throw new RuntimeException('不受支持的命令，允许：' . implode('|', Params::ACTION_LIST));
        }

        if (!isDockerEnvironment()) {
            if (!current_git_commit()) {
                throw new RuntimeException('通过git拉取的代码，才支持自动更新 https://doc.iyuu.cn/guide/install-windows');
            }
        }

        Timer::add(2, function () use ($command) {
            $cmd = implode(' ', [PHP_BINARY, base_path('start.php'), $command]);
            exec($cmd);
            sleep(3);
        });
        return ['code' => 0, 'msg' => 'ok'];
    }

    /**
     * @param string $branch
     * @return false|string
     */
    public static function checkRemoteUpdates(string $branch = 'master'): false|string
    {
        try {
            if (!current_git_commit()) {
                throw new RuntimeException('通过git拉取的代码，才支持自动更新！');
            }

            exec('git fetch', $output, $fetchStatus);
            exec("git rev-list HEAD...origin/{$branch} --count", $output, $checkStatus);

            if ($checkStatus !== 0) {
                throw new RuntimeException('通过git拉取的代码，才支持自动更新！');
            }

            $updatesCount = intval($output[0]);

            if ($updatesCount > 0) {
                return self::handleUpdates();
            } else {
                NotifyAdmin::success("已是最新版本！");
                return "已是最新版本！";
            }
        } catch (RuntimeException $e) {
            NotifyAdmin::error($e->getMessage());
            return $e->getMessage();
        }
    }

    private static function handleUpdates(): string
    {
        try {
            self::gitPull(); // 执行 Git 拉取
            self::gitaction('restart');
            NotifyAdmin::success("更新成功！");
            NotifyAdmin::setTimeout("即将自动重启！");
            return "更新成功！";
        } catch (RuntimeException $e) {
            NotifyAdmin::error($e->getMessage());
            return $e->getMessage();
        }
    }

}