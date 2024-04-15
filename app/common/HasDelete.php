<?php

namespace app\common;

use support\exception\BusinessException;
use support\Request;
use support\Response;

/**
 * 删除方法
 */
trait HasDelete
{
    /**
     * 删除
     * - 会触发模型事件
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $count = 0;
        if ($ids = $this->deleteInput($request)) {
            $count = $this->model->destroy($ids);
        }
        return $this->success('ok', ['count' => $count]);
    }
}
