<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\SpiderTorrents;

/**
 * haidan
 * - 凭cookie解析HTML列表页
 */
class CookieHaidan extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'haidan';

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
        $torrent_panel = Selector::select($html, "//div[contains(@class,'torrent_panel')]");
        if (empty($torrent_panel)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL);
        }

        //file_put_contents(runtime_path('cookie_process.txt'), print_r($torrent_panel, true));
        $list = Selector::select($html, "//div[contains(@class,'torrent_item')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL);
        }
        $items = [];
        foreach ($list as $v) {
            $arr = [];
            $matches_id = $matches_download = [];
            $vv = str_replace('&amp;', '&', $v);
            if (preg_match('/details.php\?group_id=(?<group_id>\d+)&torrent_id=(?<id>\d+)/i', $vv, $matches_id)) {
                $details = $matches_id[0];
                $arr['id'] = $matches_id['id'];
                $arr['group_id'] = $matches_id['group_id'];
                $arr['h1'] = '';
                $arr['title'] = '';
                // 详情页
                $arr['details'] = $host . $details;
                if (preg_match('/(?<download>download.php\?.*?)[\'|\"]/i', $vv, $matches_download)) {
                    $url = $matches_download['download'];
                    // 下载地址
                    $arr['download'] = $host . $url;
                    // 文件名
                    $arr['filename'] = $arr['id'] . '.torrent';
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
            throw new EmptyListException('页面解析失败C' . PHP_EOL . $html);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }

    /**
     * 爬虫模式：是否必须cookie才能下载种子
     * @return bool
     */
    protected function isSpiderDownloadCookieRequired(): bool
    {
        return false;
    }
}
