<?php

namespace Iyuu\SiteManager\Driver;

use DOMDocument;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\Unit3D\HasRss;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use RuntimeException;
use Throwable;

/**
 * hdpost
 */
class DriverHdpost extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdpost';

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
                $details = $link;
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
                    $torrent['guid'] = $id;
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
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        if ($rss_url = $this->getConfig()->get('options.rss_url')) {
            return $rss_url;
        }

        return str_replace('{rsskey}', $this->getConfig()->get('options.rsskey', ''), RouteEnum::N9->value);
    }
}
