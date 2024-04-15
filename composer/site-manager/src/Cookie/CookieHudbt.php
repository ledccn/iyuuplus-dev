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
 * hudbt
 * - 凭cookie解析HTML列表页
 */
class CookieHudbt extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hudbt';

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
        $table = Selector::select($html, '//table[@id="torrents"]');
        if (empty($table)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL);
        }

        $list = Selector::select($html, "//td[contains(@class,'torrent')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL);
        }

        $items = [];
        foreach ($list as $v) {
            $arr = [];
            $matches_id = $matches_download = $matches_h1 = [];
            if (preg_match('/details.php\?id=(?<id>\d+)/i', $v, $matches_id)) {
                $details = $matches_id[0];
                $arr['id'] = $matches_id['id'];
                if (preg_match('/(?<download>download.php\?.*?)[\'|\"]/i', $v, $matches_download)) {
                    $url = str_replace('&amp;', '&', $matches_download['download']);
                    // 获取主标题
                    if (preg_match('/<a.*? title=[\'|\"](?<h1>.*?)[\'|\"]/i', $v, $matches_h1)) {
                        $arr['h1'] = $matches_h1['h1'];
                    } else {
                        $arr['h1'] = '';
                    }
                    // 副标题
                    $arr['title'] = $this->normalizeWhitespace((new Crawler($v))->filterXPath('//h3')->text(''));

                    // 详情页
                    $arr['details'] = $host . $details;
                    // 下载地址
                    $arr['download'] = $host . $url;
                    // 文件名
                    $arr['filename'] = $arr['id'] . '.torrent';

                    if ($arr['h1'] && $arr['h1'] === $arr['title']) {
                        $arr['title'] = '';
                    }

                    // 种子促销类型解码【class="pro_free2up"】【class="pro_free"】
                    if (str_contains($v, 'class="pro_free')) {
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
            throw new EmptyListException('页面解析失败B' . PHP_EOL . $html);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }
}
