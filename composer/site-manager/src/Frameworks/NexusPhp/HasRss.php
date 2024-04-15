<?php

namespace Iyuu\SiteManager\Frameworks\NexusPhp;

use DOMDocument;
use Error;
use Exception;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestXml;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use RuntimeException;
use Throwable;

/**
 * NexusPhp的Rss订阅
 */
trait HasRss
{
    use HasRequestXml;

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
                $guid = $item->getElementsByTagName('guid')?->item(0) != null ? $item->getElementsByTagName('guid')->item(0)->nodeValue : md5($torrent_link);
                $pubDate = $item->getElementsByTagName('pubDate')?->item(0)?->nodeValue;
                $time = time();
                if ($pubDate) {
                    $time = strtotime($pubDate);
                }
                $length = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('length');
                // 提取id
                if (preg_match($this->getIdPatternInXML(), $details, $match)) {
                    $id = $match[1];
                } else {
                    continue;
                }

                $torrent['id'] = $id;
                $torrent['h1'] = $item->getElementsByTagName('title')->item(0)->nodeValue;
                $torrent['title'] = '';
                $torrent['details'] = $details;
                $torrent['download'] = $torrent_link;
                $torrent['filename'] = $id . '.torrent';
                $torrent['type'] = 0;   // 免费0
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
     * 过滤转换URL
     * @param string $url
     * @param string $domain
     * @return string
     */
    protected function filterRssUrl(string $url, string $domain): string
    {
        $url = $url ?: $this->getRssDefaultRoute();
        $url = Utils::removeSchemeHost($url);

        return rtrim($domain, '/') . '/' . ltrim($url, '/');
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
        return '/id=(\d+)/i';
    }

    /**
     * 过滤XML文档中不需要的元素
     */
    protected function filterXmlDescription($item)
    {
        $node = $item->getElementsByTagName('description')->item(0);
        if (null !== $node) {
            return $item->removeChild($node);
        }
        return $item;
    }
}
