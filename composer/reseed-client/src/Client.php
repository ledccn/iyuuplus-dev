<?php

namespace Iyuu\ReseedClient;

use RuntimeException;

/**
 * 辅种客户端
 */
class Client extends AbstractCurl
{
    /**
     * 主域名
     */
    protected const string BASE_API = 'https://dev.iyuu.cn';

    /**
     * 获取站点列表
     * @return array
     */
    public function sites(): array
    {
        $curl = $this->getCurl()->get(self::BASE_API . '/reseed/sites/index');
        if (!$curl->isSuccess()) {
            throw new RuntimeException($curl->error_message ?? '获取站点列表失败，服务器无响应', 500);
        }

        $response = json_decode($curl->response, true);
        $code = $response['code'] ?? 403;
        $msg = $response['msg'] ?? '获取站点列表失败，缺失错误信息';
        if ($code) {
            throw new RuntimeException($msg, $code);
        }

        return array_column($response['data']['sites'], null, 'site');
    }
}
