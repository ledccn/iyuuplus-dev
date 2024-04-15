<?php

namespace plugin\cron\app\admin\controller;

use plugin\admin\app\controller\Base;
use support\Request;
use support\Response;

/**
 * 公共控制器
 */
class CommonController extends Base
{
    /**
     * 所有命令
     * @param Request $request
     * @return Response
     */
    public function commands(Request $request): Response
    {
        return $this->success('', webman_commands());
    }
}
