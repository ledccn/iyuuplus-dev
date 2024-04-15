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
 * pter
 * - 凭cookie解析HTML列表页
 */
class CookiePter extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'pter';

    /**
     * 解析器
     * @param string $url 列表URL
     * @return array
     * @throws EmptyListException
     * @deprecated
     */
    public function process(string $url): array
    {
        $domain = $this->getConfig()->parseDomain();
        $host = $domain . '/';
        $html = $this->requestHtml($this->filterListUrl($url, $domain));
        $list = Selector::select($html, "//table[contains(@class,'torrentname')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败A');
        }

        $items = [];
        foreach ($list as $v) {
            $arr = [];
            // 主标题、详情页
            if (preg_match('/<a title=[\'|\"](?<h1>.*?)[\'|\"](.*?)href=[\'|\"](?<details>.*?)[\'|\"]/i', $v, $matches)) {
                // 种子ID
                if (preg_match('/details.php\?id=(\d+)/i', $matches['details'], $matches_id)) {
                    $arr['id'] = $matches_id[1];
                    $arr['h1'] = $matches['h1'];
                    // 种子地址
                    if (preg_match('/(?<download>download.php\?.*?)[\'|\"]/i', $v, $matches_download)) {
                        $url = $matches_download['download'];
                        $crawler = new Crawler($v);
                        $arr['title'] = $crawler->filterXPath('//div/span')->text('');
                        // 组合返回数组
                        $arr['details'] = $host . $matches['details'];
                        $arr['download'] = $host . str_replace('&amp;', '&', $url);
                        $arr['filename'] = $arr['id'] . '.torrent';

                        // 种子促销类型解码
                        if (str_contains($v, 'class="pro_free')) {
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
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL . $html);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }
}
