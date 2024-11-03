<?php

namespace app\admin\controller;

use app\admin\services\SystemServices;
use app\common\HasJsonResponse;
use support\Request;
use support\Response;
use Throwable;

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
        try {
            $command = $request->post('command', 'restart');
            return json(SystemServices::gitAction($command));
        } catch (Throwable $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 拉取最新代码
     * @param Request $request
     * @return Response
     */
    public function pull(Request $request): Response
    {
        try {
            $data = SystemServices::gitPull();
            return $this->success('ok', ['status' => $data['status'], 'output' => $data['output']]);
        } catch (Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
    }
}
