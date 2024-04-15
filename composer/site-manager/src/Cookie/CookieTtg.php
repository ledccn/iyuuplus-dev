<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Symfony\Component\DomCrawler\Crawler;

/**
 * ttg
 * - 凭cookie解析HTML列表页
 */
class CookieTtg extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'ttg';

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
        $html = $this->requestHtml($this->filterListUrl($url, $domain));
        $torrent_table = Selector::select($html, "//table[@id='torrent_table']");
        if (empty($torrent_table)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL . $html);
        }

        $list = Selector::select($html, "//tr[contains(@class,'hover_hr')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL . $html);
        }

        $items = [];
        foreach ($list as $v) {
            $arr = [];
            $matches_id = $matches_download = $matches_h1 = [];
            if (preg_match('#/t/(?<id>\d+)/#i', $v, $matches_id)) {
                $details = $matches_id[0];
                $arr['id'] = $matches_id['id'];
                if (preg_match('#(?<download>/dl/\d+/\d+)[\'|\"]#i', $v, $matches_download)) {
                    $download = str_replace('&amp;', '&', $matches_download['download']);
                    // 获取主标题
                    $arr['h1'] = '';
                    // 副标题
                    $arr['title'] = '';
                    if (preg_match('#<b>(?<h1>.*?)</b>#i', $v, $matches_h1)) {
                        $result = explode('<br />', $matches_h1['h1']);
                        if (count($result) === 2) {
                            [$h1, $title] = $result;
                            $arr['h1'] = $this->normalizeWhitespace($h1);
                            if ($title) {
                                $arr['title'] = (new Crawler($title))->text('');
                            }
                        } else {
                            $arr['h1'] = $this->normalizeWhitespace($result[0]);
                        }
                    }

                    // 详情页
                    $arr['details'] = $domain . $details;
                    // 下载地址
                    $arr['download'] = $domain . $download;
                    // 文件名
                    $arr['filename'] = $arr['id'] . '.torrent';

                    // 种子促销类型解码【class="pro_free2up"】【class="pro_free"】
                    if (str_contains($v, 'ico_free.gif')) {
                        // 免费种子
                        $arr['type'] = 0;
                    } else {
                        // 不免费
                        $arr['type'] = 1;
                    }

                    // H&R检测
                    $arr['hr'] = false;
                    foreach (['hit_run.gif', 'title="Hit and Run"', 'title="Hit'] as $mark) {
                        if (str_contains($v, $mark)) {
                            $arr['hr'] = true;
                            break;
                        }
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
            throw new EmptyListException('页面解析失败B');
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }

    /**
     * 获取默认的列表路由规则
     * @return string
     */
    protected function getListDefaultRoute(): string
    {
        return RouteEnum::N8->value;
    }
}
