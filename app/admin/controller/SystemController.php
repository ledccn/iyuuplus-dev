<?php

namespace app\admin\controller;

use app\common\HasJsonResponse;
use Iyuu\SiteManager\Spider\Params;
use support\Request;
use support\Response;

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
        if (!in_array($command, Params::TYPE_LIST, true)) {
            return $this->fail('不受支持的命令，允许：' . implode('|', Params::TYPE_LIST));
        }

        $cmd = implode(' ', ['php', base_path('start.php'), $command]);
        exec($cmd);
        sleep(3);
        return json(['code' => 0, 'msg' => 'ok']);
    }
}
