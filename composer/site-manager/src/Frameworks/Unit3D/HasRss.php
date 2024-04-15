<?php

namespace Iyuu\SiteManager\Frameworks\Unit3D;

use DOMDocument;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestXml;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use RuntimeException;
use Throwable;

/**
 * Unit3D的Rss订阅
 */
trait HasRss
{
    use HasRequestXml;

    /**
     * RSS订阅XML的契约方法
     * @param string $url
     * @return array
     * @throws EmptyListException
     */
    public function processXml(string $url = ''): array
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
                $link = $item->getElementsByTagName('link')->item(0)->nodeValue;
                $guid = $item->getElementsByTagName('guid')->item(0) != null ? $item->getElementsByTagName('guid')->item(0)->nodeValue : md5($link);
                $details = $link;
                $size = $item->getElementsByTagName('size')->item(0)->nodeValue;
                $time = strtotime($item->getElementsByTagName('pubDate')->item(0)->nodeValue);
                // 提取id
                if (preg_match($this->getIdPatternInXML(), $details, $matches)) {
                    $id = $matches['id'];
                    $torrent['id'] = $id;
                    $torrent['h1'] = $item->getElementsByTagName('title')->item(0)->nodeValue;
                    $torrent['title'] = '';
                    $torrent['details'] = $details;
                    $torrent['download'] = $link;
                    $torrent['rsskey'] = $matches['rsskey'];
                    $torrent['filename'] = $id . '.torrent';
                    $torrent['type'] = 0;   // 免费0
                    $torrent['time'] = date("Y-m-d H:i:s", $time);
                    $torrent['size'] = str_replace(' ', '', $size);
                    //$torrent['length'] = $length;
                    $torrent['guid'] = $guid;
                    $items[] = $torrent;
                }
            }
        } catch (Throwable $throwable) {
            throw new RuntimeException('XML页面解析失败' . $throwable->getMessage() . PHP_EOL);
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败B');
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
        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            $info = parse_url($url);
            $url = str_replace($info['scheme'] . '://' . $info['host'], '', $url);
        }

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

        return str_replace('{rsskey}', $this->getConfig()->get('options.rsskey', ''), RouteEnum::N4->value);
    }

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '#/torrent/download/(?<id>\d+)\.(?<rsskey>[A-Za-z0-9]+)#i';
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
