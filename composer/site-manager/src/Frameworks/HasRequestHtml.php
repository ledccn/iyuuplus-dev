<?php

namespace Iyuu\SiteManager\Frameworks;

use Ledc\Curl\Curl;
use RuntimeException;

/**
 * 请求Html配置
 */
trait HasRequestHtml
{
    /**
     * 请求html页面
     * @param string $url
     * @return string
     */
    protected function requestHtml(string $url): string
    {
        $curl = new Curl();
        $config = $this->getConfig();
        $config->setCurlOptions($curl);
        $curl->setCookies($config->get('cookie', $config->get('cookies', '')));
        $this->beforeRequestHtml($curl);
        $curl->get($url);
        if (!$curl->isSuccess()) {
            $errmsg = $curl->error_message ?? '网络不通或cookies过期';
            throw new RuntimeException('下载HTML失败：' . $errmsg);
        }

        $html = $curl->response;
        if (is_bool($html) || empty($html)) {
            throw new RuntimeException('下载HTML失败：curl_exec返回错误');
        }
        return $html;
    }

    /**
     * 请求html页面前回调
     * @param Curl $curl
     * @return void
     */
    protected function beforeRequestHtml(Curl $curl): void
    {
    }
}
