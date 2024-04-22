<?php

namespace app\admin\controller;

use app\common\HasJsonResponse;
use Iyuu\SiteManager\Spider\Params;
use support\Request;
use support\Response;
use Symfony\Component\Process\Process;

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
            return $this->fail('docker环境存在s6时，才能进行此操作');
        }

        $cmd = implode(' ', ['php', base_path('start.php'), $command]);
        exec($cmd);
        sleep(3);
        return json(['code' => 0, 'msg' => 'ok']);
    }

    /**
     * 重启
     * @param Request $request
     * @return Response
     */
    public function restart(Request $request): Response
    {
        safe_webman_stop();
        return $this->success();
    }

    /**
     * 拉取最新代码
     * @param Request $request
     * @return Response
     */
    public function pull(Request $request): Response
    {
        $process = new Process(['git', 'pull'], base_path(), null, null, 10);
        $process->run();
        $status = $process->getExitCode();
        $output = $process->getOutput();
        return $status ? $this->fail('刷新失败') : $this->success('ok', ['status' => $status, 'output' => $output]);
    }
}
