<?php

namespace Iyuu\SiteManager\Frameworks\Yemapt;

use JsonException;
use Ledc\Curl\Curl;
use RuntimeException;

/**
 * 野马PT开放API请求能力
 */
trait HasOpenApi
{
    /**
     * 调用开放API的POST接口并返回ResultDTO.data
     * @param string $uri API路径，可以包含查询参数
     * @param array $data JSON请求体
     */
    protected function postOpenApi(string $uri, array $data = []): mixed
    {
        $domain = rtrim($this->getConfig()->parseDomain(), '/');
        $curl = new Curl();
        $this->getConfig()->setCurlOptions($curl);
        $curl->setAccept('application/json')
            ->setHeader('Authorization', (string)$this->getConfig()->getOptions('authkey'));
        $url = $domain . '/' . ltrim($uri, '/');
        if ([] === $data) {
            $curl->post($url);
        } else {
            $curl->post($url, $data, true);
        }

        $response = is_string($curl->response) ? $curl->response : '';
        try {
            $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            if (!$curl->isSuccess()) {
                $message = $curl->error_message ?? '网络不通或AuthKey无效';
                throw new RuntimeException('请求野马PT开放API失败：' . $message, $curl->error_code);
            }
            throw new RuntimeException('野马PT开放API响应JSON解析失败：' . $exception->getMessage(), $exception->getCode());
        }

        if (!is_array($result) || !array_key_exists('success', $result)) {
            throw new RuntimeException('野马PT开放API响应格式异常');
        }

        if (true !== $result['success']) {
            $message = (string)($result['errorMessage'] ?? '未知错误');
            throw new RuntimeException('野马PT开放API请求失败：' . $message, (int)($result['errorCode'] ?? 0));
        }

        if (!$curl->isSuccess()) {
            $message = $curl->error_message ?? 'HTTP请求失败';
            throw new RuntimeException('请求野马PT开放API失败：' . $message, $curl->error_code);
        }

        return $result['data'] ?? null;
    }
}
