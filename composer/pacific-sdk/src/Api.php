<?php

namespace Iyuu\PacificSdk;

use Iyuu\PacificSdk\Contracts\ResponsePusher;

/**
 * API接口
 */
class Api extends Pacific
{
    /**
     * @return ResponsePusher
     */
    public function getPusher(): ResponsePusher
    {
        $curl = $this->curl;
        $curl->get($this->serverAddress);
        if ($curl->isSuccess()) {
            $response = $this->parseResponseData($curl);
            return new ResponsePusher($response);
        }

        $this->throwException($curl);
    }

    /**
     * 更新下载状态
     * @param int $id 下载主键
     * @param int $status 状态码：1已调度、2失败、3成功
     * @param string $message 描述消息
     * @return void
     */
    public function update(int $id, int $status, string $message): void
    {
        $data = [
            'id' => $id,
            'status' => $status,
            'message' => $message
        ];
        $curl = $this->curl;
        $curl->post($this->host . '/likeadmin/UserDownload/update', $data);
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        // TODO: Implement initialize() method.
    }
}