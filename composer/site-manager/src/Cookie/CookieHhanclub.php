<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Helper;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\SpiderTorrents;

/**
 * hhanclub
 * - 凭cookie解析HTML列表页
 * - 站点样式基于 https://www.tailwindcss.cn/
 */
class CookieHhanclub extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hhanclub';

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
        $table = Selector::select($html, "//div[contains(@class,'torrent-table-for-spider')]");
        if (empty($table)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL);
        }

        $list = Selector::select($html, "//div[contains(@class,'torrent-table-sub-info')]");
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
                    $crawler = Helper::makeCrawler($v);
                    // 获取主标题
                    $arr['h1'] = $crawler->filterXPath('//a[contains(@class,"torrent-info-text-name")]')->text('');
                    // 副标题
                    $arr['title'] = $this->normalizeWhitespace($crawler->filterXPath('//div[contains(@class,"torrent-info-text-small_name")]')->text(''));
                    // 详情页
                    $arr['details'] = $host . $details;
                    // 下载地址
                    $arr['download'] = $host . $url;
                    // 文件名
                    $arr['filename'] = $arr['id'] . '.torrent';
                    // 种子促销类型解码【class="pro_free2up"】【class="pro_free"】
                    if (str_contains($v, 'promotion-tag-free')) {
                        // 免费种子
                        $arr['type'] = 0;
                    } else {
                        // 不免费
                        $arr['type'] = 1;
                    }
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
