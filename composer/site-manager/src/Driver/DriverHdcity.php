<?php

namespace Iyuu\SiteManager\Driver;

use DOMDocument;
use Error;
use Exception;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use Ledc\Curl\Curl;
use RuntimeException;
use Throwable;

/**
 * hdcity
 */
class DriverHdcity extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 种子下载链接的正则表达式cuhash
     */
    public const REGEX = '#download\?id=\d+(?:&|&amp;)cuhash=([A-Za-z0-9]+)#i';

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdcity';

    /**
     * 契约方法
     * @param string $url RSS网址
     * @return array
     * @throws EmptyListException
     */
    public function processXml(string $url): array
    {
        $domain = $this->getConfig()->parseDomain();
        $xml = $this->requestXml($this->filterRssUrl($url, $domain));
        $items = [];

        try {
            $dom = new DOMDocument();
            // 禁用标准的 libxml 错误
            libxml_use_internal_errors(true);
            $dom->loadXML($xml);
            // 清空 libxml 错误缓冲
            libxml_clear_errors();
            $elements = $dom->getElementsByTagName('item');
            /** @var DOMDocument $item */
            foreach ($elements as $item) {
                $this->filterXmlDescription($item);
                $details = $item->getElementsByTagName('link')->item(0)->nodeValue;
                $torrent_link = $item->getElementsByTagName('enclosure')->item(0) != null ? $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url') : $details;
                $time = time();
                $length = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('length');
                // 提取id
                if (preg_match($this->getIdPatternInXML(), $details, $match)) {
                    $id = $match[1];
                    $guid = $id;
                } else {
                    continue;
                }

                $torrent['id'] = $id;
                $torrent['h1'] = $item->getElementsByTagName('title')->item(0)->nodeValue;
                $torrent['title'] = '';
                $torrent['details'] = $domain . '/t-' . $id;
                $torrent['download'] = $torrent_link; // 都可以下载种子：$torrent_link 或者 $details
                $torrent['filename'] = $id . '.torrent';
                $torrent['type'] = 1;   // 免费0
                $torrent['time'] = date("Y-m-d H:i:s", $time);
                $torrent['size'] = Utils::dataSize($length);
                $torrent['length'] = $length;
                $torrent['guid'] = $guid;
                $items[] = $torrent;
            }
        } catch (Error|Exception|Throwable $e) {
            throw new RuntimeException('XML页面解析失败' . $e->getMessage());
        }

        if (empty($items)) {
            throw new EmptyListException('XML页面解析失败B' . PHP_EOL . $xml);
        }
        SpiderTorrents::notify($items, $this, $this->isRssDownloadCookieRequired());
        return $items;
    }

    /**
     * 请求下载种子前回调
     * @param Curl $curl
     * @return void
     */
    protected function beforeDownload(Curl $curl): void
    {
        parent::beforeDownload($curl);
        $curl->setFollowLocation(1);
    }

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '/&t=(\d+)/i';
    }

    /**
     * 解析生成替换规则
     * @param Torrent $torrent
     * @return array
     */
    protected function parseReplace(Torrent $torrent): array
    {
        return [
            '{}' => $torrent->torrent_id,
            '{id}' => $torrent->torrent_id,
            '{passkey}' => $this->getConfig()->get('options.passkey', ''),
            '{cuhash}' => $this->getConfig()->get('options.cuhash', ''),
        ];
    }

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        return str_replace('{passkey}', $this->getConfig()->get('options.passkey', ''), RouteEnum::N7->value);
    }
}
