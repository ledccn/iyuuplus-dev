<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\Unit3D\HasCookie;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\SpiderTorrents;

/**
 * hdpost
 * - 凭cookie解析HTML列表页
 */
class CookieHdpost extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdpost';

    /**
     * 契约方法
     * - 解析页面生成数据
     * @param string $url 列表URL
     * @return array
     * @throws EmptyListException
     */
    public function process(string $url): array
    {
        $domain = $this->getConfig()->parseDomain();
        $host = $domain . '/';
        $html = $this->requestHtml($this->filterListUrl($url, $domain));

        $items = [];
        try {
            $table = Selector::select($html, '//table[contains(@class,"data-table")]/tbody');
            if (empty($table)) {
                throw new EmptyListException('页面解析失败A');
            }

            $list = Selector::select($table, '//tr');
            foreach ($list as $v) {
                $tr = [];
                $regex = $this->getHtmlDownloadRegex($domain);
                if (preg_match($regex, $v, $matches)) {
                    $tr['id'] = $matches[1];
                    $h1 = Selector::select($v, '//a[contains(@class,"torrent-search--list__name")]');
                    $tr['h1'] = $this->filterText($h1);
                    $tr['title'] = '';
                    $details = Selector::select($v, '//a[contains(@class,"torrent-search--list__name")]/@href');
                    $tr['details'] = $details ?: $host . 'torrents/' . $tr['id'];
                    $tr['download'] = $matches[0];
                    $tr['filename'] = $tr['id'] . '.torrent';
                    //下载是否消耗流量：0免费/1不免费
                    $tr['type'] = 0;
                    $items[] = $tr;
                }
            }
            // 存活时间
            // 大小
            // 种子数
            // 下载数
            // 完成数
            // 完成进度
        } catch (\Error|\Exception|\Throwable $throwable) {
            throw new \RuntimeException($throwable->getMessage(), $throwable->getCode());
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败B');
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }

    /**
     * 获取正则表达式
     * @param string $domain
     * @return string
     */
    protected function getHtmlDownloadRegex(string $domain): string
    {
        return "#{$domain}/torrents/download/(\d+)#i";
    }
}
