<?php

namespace Iyuu\SiteManager\Driver;

use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Ledc\Curl\Curl;

/**
 * 可乐PT（ColaPT）
 *
 * 自研 Go 站点，RSS 按 NexusPHP 口径输出，凭据走 x-api-key 请求头（不放 URL）。
 *
 * 只实现 ProcessorXml：站点是 SPA，首页没有旧式登录锚点，HTML 列表页不存在 ——
 * 若实现 Processor，makeBaseCookie() 会抛「Cookie Processor [colapt] not supported」。
 */
class DriverColapt extends BaseDriver implements ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'colapt';

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
        if (empty($this->getConfig()->getOptions('x_api_key'))) {
            throw new InvalidArgumentException('可乐PT 缺少 x-api-key 存取令牌');
        }
    }

    /**
     * RSS 请求带上令牌
     * @param Curl $curl
     * @return void
     */
    protected function beforeRequestXml(Curl $curl): void
    {
        $curl->setFollowLocation(1);
        $curl->setHeader('x-api-key', $this->getConfig()->getOptions('x_api_key'));
    }

    /**
     * 上报链取 .torrent 时也带令牌
     *
     * 下载走的是 spider 里的 enclosure url，里面没有 passkey，认证全靠这个头。
     * downloadMetadata() 的第一步就是 beforeDownload()，所以这里加就够。
     *
     * @param Curl $curl
     * @return void
     */
    protected function beforeDownload(Curl $curl): void
    {
        parent::beforeDownload($curl);
        $curl->setHeader('x-api-key', $this->getConfig()->getOptions('x_api_key'));
    }
}
