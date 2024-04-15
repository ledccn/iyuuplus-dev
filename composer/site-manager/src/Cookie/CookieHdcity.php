<?php

namespace Iyuu\SiteManager\Cookie;

use Error;
use Exception;
use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use support\Log;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * hdcity
 * - 凭cookie解析HTML列表页
 */
class CookieHdcity extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdcity';

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
        $list = Selector::select($html, "//div[contains(@class,'tr_normal trblock')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL);
        }

        $items = [];
        foreach ($list as $v) {
            $arr = [];
            $matches_id = $matches_download = $matches_h1 = [];
            if (preg_match('/t-(?<id>\d+)/i', $v, $matches_id)) {
                $details = $matches_id[0];
                $arr['id'] = $matches_id['id'];
                if (preg_match('/(?<download>download\?.*?)[\'|\"]/i', $v, $matches_download)) {
                    $url = str_replace('&amp;', '&', $matches_download['download']);
                    try {
                        // 获取主标题
                        $crawler = new Crawler($v);
                        $arr['h1'] = $this->normalizeWhitespace($crawler->filterXPath('//a[contains(@class,"torname")]')->text(''));
                        // 副标题
                        $arr['title'] = $this->normalizeWhitespace($crawler->filterXPath('//div[contains(@class,"trbi")]')->text(''));
                    } catch (Error|Exception|Throwable $throwable) {
                        Log::error(__METHOD__ . ' 解析副标题异常：' . $throwable->getMessage() . sprintf('站点：%s | 详情页：%s', $this->getConfig()->site, $host . $details));
                        $arr['title'] = '';
                    }

                    // 详情页
                    $arr['details'] = $host . $details;
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
            throw new EmptyListException('页面解析失败B' . PHP_EOL . $html);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }
}
