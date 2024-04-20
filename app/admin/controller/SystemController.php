<?php

namespace app\admin\controller;

use support\Request;
use support\Response;

/**
 * 系统管理
 */
class SystemController
{
    /**
     * 执行操作
     * - 启动、重启、停止、重载等webman支持的命令
     * @param Request $request
     * @return Response
     */
    public function action(Request $request): Response
    {
        $action = $request->post('action', 'restart');
        $cmd = implode(' ', ['php', base_path('start.php'), $action]);
        exec($cmd);
        sleep(3);
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
