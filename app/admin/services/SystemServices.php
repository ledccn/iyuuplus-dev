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
     * 启动、重启、停止、重载等webman支持的命令
     * - 必须在docker的S6环境
     * @param string $command
     * @return array
     */
    public static function gitAction(string $command): array
    {
        if (!in_array($command, Params::ACTION_LIST, true)) {
            throw new RuntimeException('不受支持的命令，允许：' . implode('|', Params::ACTION_LIST));
        }

        // 程序无法重启自身，必须用非亲缘关系的进程才能重启
        // 在docker的S6环境，webman可以给自身发送stop、restart指令，这时webman会进程会自杀；S6会重新拉起它
        if (!isDockerEnvironment()) {
            if (current_git_commit()) {
                throw new RuntimeException('请重启IYUU，即可更新成功');
            } else {
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
     * 自动更新并重启
     * - 必须在docker的S6环境
     * @param string $branch
     * @return false|string
     */
    public static function checkRemoteUpdates(string $branch = 'master'): false|string
    {
        try {
            if (!current_git_commit()) {
                throw new RuntimeException('通过git拉取的代码，才支持自动更新！');
            }

            $process = new Process(['git', 'fetch'], base_path());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException('检查失败：无法拉取代码！');
            }

            $process = new Process(["git", "rev-list", "HEAD...origin/{$branch}", "--count"], base_path());
            $process->run();

            if (!$process->isSuccessful()) {
                throw new RuntimeException('检查失败：无法拉取代码，可能不是通过git拉取的代码，才支持自动更新');
            }

            $updatesCount = intval(trim($process->getOutput()));
            if ($updatesCount > 0) {
                (new Process(['git', 'reset --hard origin/master'], base_path()))->run();
                self::gitAction('restart');
                NotifyAdmin::success("更新成功！");
                NotifyAdmin::setTimeout("即将自动重启！");
                return "更新成功！";
            } else {
                NotifyAdmin::success("已是最新版本！");
                return "已是最新版本！";
            }
        } catch (RuntimeException $e) {
            (new Process(['git', 'reset --hard origin/master'], base_path()))->run();
            NotifyAdmin::error($e->getMessage());
            return $e->getMessage();
        }
    }
}
