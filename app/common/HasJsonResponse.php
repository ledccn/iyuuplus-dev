<?php

namespace app\common;

use support\Response;

/**
 * Json响应
 */
trait HasJsonResponse
{
    /**
     * 成功时code值
     * @return int
     */
    protected function successCode(): int
    {
        return 0;
    }

    /**
     * 返回格式化json数据
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return Response
     */
    protected function json(int $code, string $msg = 'ok', array $data = []): Response
    {
        return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
    }

    /**
     * 成功响应
     * @param array $data
     * @return Response
     */
    protected function data(array $data): Response
    {
        return json(['code' => $this->successCode(), 'data' => $data, 'msg' => 'ok']);
    }

    /**
     * 成功响应
     * @param string $msg
     * @param array $data
     * @return Response
     */
    protected function success(string $msg = '成功', array $data = []): Response
    {
        return $this->json($this->successCode(), $msg, $data);
    }

    /**
     * 失败响应
     * @param string $msg
     * @param array $data
     * @return Response
     */
    protected function fail(string $msg = '失败', array $data = []): Response
    {
        return $this->json(1, $msg, $data);
    }
}
