<?php

namespace app\common;

use plugin\admin\app\common\Util;
use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 清空表方法
 */
trait HasClear
{
    /**
     * 清空表
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function clear(Request $request): Response
    {
        $request->canOnlyPost();
        $rs = Util::db()->statement('TRUNCATE TABLE ' . $this->model->getTable());
        return $rs ? $this->success() : $this->fail('清理失败');
    }
}
