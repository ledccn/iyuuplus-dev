<?php

namespace Iyuu\PacificSdk;

use Iyuu\PacificSdk\Contracts\ResponsePusher;

/**
 * API接口
 */
class Api extends Pacific
{
    /**
     * 服务器地址
     */
    protected const string SERVER_URL = 'http://v.hilx.cn';
    /**
     * 接入点
     */
    protected const array ENDPOINT = [
        'device' => '/likeadmin/device/index',
        'deviceGenerate' => '/likeadmin/device/generate',
        'deviceBind' => '/likeadmin/device/bind',
        'userDownloadUpdate' => '/likeadmin/UserDownload/update',
        'pushAuth' => '/plugin/webman/push/auth',
    ];

    /**
     * @return ResponsePusher
     */
    public function getPusher(): ResponsePusher
    {
        $curl = $this->curl;
        $curl->get(self::SERVER_URL . self::ENDPOINT['device']);
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
        $curl->post(self::SERVER_URL . self::ENDPOINT['userDownloadUpdate'], $data);
    }

    /**
     * @return void
     */
    protected function initialize(): void
    {
        // TODO: Implement initialize() method.
    }
}
