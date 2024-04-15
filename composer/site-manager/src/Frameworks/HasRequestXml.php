<?php

namespace Iyuu\SiteManager\Frameworks;

use Ledc\Curl\Curl;
use RuntimeException;

/**
 * 请求url获取xml文档
 */
trait HasRequestXml
{
    /**
     * 请求url获取xml文档
     * @param string $url
     * @return string
     */
    protected function requestXml(string $url): string
    {
        $curl = new Curl();
        $this->getConfig()->setCurlOptions($curl);
        $curl->get($url);
        if (!$curl->isSuccess()) {
            $errmsg = $curl->error_message ?: 'error_message错误消息为空';
            throw new RuntimeException('下载XML失败：' . $errmsg);
        }

        $xml = $curl->response;
        if (is_bool($xml) || empty($xml)) {
            throw new RuntimeException('下载XML失败：curl_exec返回错误');
        }
        return $xml;
    }
}
