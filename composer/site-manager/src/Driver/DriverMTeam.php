<?php

namespace Iyuu\SiteManager\Driver;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;
use Ledc\Curl\Curl;
use Throwable;

/**
 * m-team
 */
class DriverMTeam extends BaseDriver implements ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'm-team';

    /**
     * 设置Curl的存取令牌
     * @param Curl $curl
     * @return Curl
     */
    public function setCurlXApiKey(Curl $curl): Curl
    {
        return $curl->setHeader('x-api-key', $this->getConfig()->getOptions('x_api_key'));
    }

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        if ($rss_url = $this->getConfig()->get('options.rss_url')) {
            return $rss_url;
        }
        return str_replace('{passkey}', $this->getConfig()->get('options.passkey', ''), RouteEnum::N2->value);
    }

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '#detail/(\d+)#i';
    }

    /**
     * 生成下载种子的完整的URL
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $domain = $this->getConfig()->parseDomain();
            $uri = $this->getConfig()->parseUri();

            $curl = new Curl();
            $this->getConfig()->setCurlOptions($curl);
            $this->setCurlXApiKey($curl);
            $curl->upload($domain . '/' . $uri, ['id' => $torrent->torrent_id]);
            if ($curl->isSuccess()) {
                $result = json_decode($curl->response);
                $code = $result->code ?? null;
                if ('0' === $code && !empty($result->data)) {
                    // 未处理url_join参数
                    $torrent->setDownload($result->data, false);
                    return $torrent->download;
                }

                if (!empty($result->message)) {
                    throw new InvalidArgumentException('获取mteam种子链接时失败：' . $result->message);
                }
            }
            $this->throwException($curl);
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * @param Curl $curl
     * @return void
     */
    protected function beforeDownload(Curl $curl): void
    {
        parent::beforeDownload($curl);
        $curl->setFollowLocation(1);
    }
}
