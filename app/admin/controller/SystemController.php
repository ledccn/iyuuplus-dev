<?php

namespace app\admin\controller;

use app\common\HasJsonResponse;
use Iyuu\SiteManager\Spider\Params;
use support\Request;
use support\Response;
use Symfony\Component\Process\Process;
use Workerman\Timer;

/**
 * 系统管理
 */
class SystemController
{
    use HasJsonResponse;

    /**
     * 执行操作
     * - 启动、重启、停止、重载等webman支持的命令
     * @param Request $request
     * @return Response
     */
    public function action(Request $request): Response
    {
        $command = $request->post('command', 'restart');
        if (!in_array($command, Params::ACTION_LIST, true)) {
            return $this->fail('不受支持的命令，允许：' . implode('|', Params::ACTION_LIST));
        }

        if (!isDockerEnvironment()) {
            if (current_git_commit()) {
                return $this->fail('请重启IYUU，即可更新成功');
            } else {
                return $this->fail('通过git拉取的代码，才支持自动更新 https://doc.iyuu.cn/guide/install-windows');
            }
        }

        Timer::add(2, function () use ($command) {
            $cmd = implode(' ', [PHP_BINARY, base_path('start.php'), $command]);
            exec($cmd);
            sleep(3);
        });
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 拉取最新代码
     * @param Request $request
     * @return Response
     */
    public function pull(Request $request): Response
    {
        //exec('git pull', $result);
        $command = DIRECTORY_SEPARATOR === '\\' ? ['git', 'pull'] : ['sh', base_path('gg.sh')];
        $process = new Process($command, base_path(), null, null, 30);
        $process->run();
        $status = $process->getExitCode();
        $output = $process->getOutput();
        return $status ? $this->fail('刷新失败：' . json_encode($output, JSON_UNESCAPED_UNICODE)) : $this->success('ok', ['status' => $status, 'output' => $output]);
    }
}
