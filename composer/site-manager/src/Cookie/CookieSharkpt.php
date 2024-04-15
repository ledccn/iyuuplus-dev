<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Symfony\Component\DomCrawler\Crawler;

/**
 * sharkpt
 * - 凭cookie解析HTML列表页
 */
class CookieSharkpt extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'sharkpt';

    /**
     * 契约方法
     *  - 解析页面生成数据
     * @param string $url
     * @return array
     * @throws EmptyListException
     */
    public function process(string $url): array
    {
        $domain = $this->getConfig()->parseDomain();
        $host = $domain . '/';
        $html = $this->requestHtml($this->filterListUrl($url, $domain));
        $table = Selector::select($html, "//div[contains(@class,'s-table-body')]");
        if (empty($table)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL);
        }

        $list = Selector::select($html, "//div[contains(@class,'torrent-item')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL);
        }

        $items = [];
        foreach ($list as $v) {
            $arr = [];
            $matches_id = $matches_download = [];
            if (preg_match('/details.php\?id=(?<id>\d+)/i', $v, $matches_id)) {
                $details = $matches_id[0];
                $arr['id'] = $matches_id['id'];
                if (preg_match('/(?<download>download.php\?.*?)[\'|\"]/i', $v, $matches_download)) {
                    $url = str_replace('&amp;', '&', $matches_download['download']);
                    $torrentItem = new Crawler($v);
                    $arr['h1'] = $torrentItem->filterXPath('//a[contains(@class,"torrent-title-label")]')->text('');
                    // 副标题
                    $arr['title'] = $this->normalizeWhitespace($torrentItem->filterXPath('//div[contains(@class,"torrent-subtitle")]')->text(''));
                    // 详情页
                    $arr['details'] = $host . $details;
                    // 下载地址
                    $arr['download'] = $host . $url;
                    // 文件名
                    $arr['filename'] = $arr['id'] . '.torrent';
                    // 种子促销类型
                    $torrentTags = $torrentItem->filterXPath('//div[contains(@class,"torrent-tags")]')->text('');
                    if (str_contains($torrentTags, 'FREE')) {
                        // 免费种子
                        $arr['type'] = 0;
                    } else {
                        // 不免费
                        $arr['type'] = 1;
                    }
                    // 存活时间
                    // 大小
                    // 种子数
                    // 下载数
                    // 完成数
                    // 完成进度
                    $items[] = $arr;
                }
            }
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败C' . PHP_EOL);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }
}
