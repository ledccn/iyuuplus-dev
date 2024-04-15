<?php

namespace Iyuu\SiteManager\Frameworks\Unit3D;

use Error;
use Exception;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestHtml;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * 爬虫框架，Unit3D
 */
trait HasCookie
{
    use HasRequestHtml;

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
            $table = Selector::select($html, '//*[@id="torrent-list-table"]');
            if (empty($table)) {
                throw new EmptyListException('页面解析失败A');
            }

            $list = Selector::select($html, '//*[@id="torrent-list-table"]//tbody//tr');
            foreach ($list as $v) {
                $tr = [];
                $regex = $this->getHtmlDownloadRegex($domain);
                if (preg_match($regex, $v, $matches)) {
                    $tr['id'] = $matches[1];
                    $h1 = Selector::select($v, '//a[contains(@class,"torrent-listings-name")]');
                    $tr['h1'] = $this->filterText($h1);
                    $title = Selector::select($v, '//span[contains(@class,"torrent-listings-subhead")]/b');
                    $tr['title'] = $this->filterText($title);
                    $details = Selector::select($v, '//*[contains(@class,"torrent-listings-name")]/@href');
                    $tr['details'] = $details ?: $host . 'torrents/' . $tr['id'];
                    $tr['download'] = $matches[0];
                    $tr['rsskey'] = $matches[2];
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
        } catch (Error|Exception|Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage(), $throwable->getCode());
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
        return "#{$domain}/torrents/download/(\d+)\.([A-Za-z0-9]+)#i";
    }

    /**
     * 过滤主标题
     * @param string|null $text
     * @return string
     */
    private function filterText(string $text = null): string
    {
        static $disallow = ["\0", "\r", "\n"];
        return $text ? trim(strip_tags(str_replace($disallow, '', $text))) : '';
    }

    /**
     * 解析种子下载链接
     * @param Crawler $node
     * @param string $host
     * @return string
     */
    protected function parseDownloadLink(Crawler $node, string $host): string
    {
        return $host . ltrim($node->filterXPath('//td[2]/a')->attr('href'), '/');
    }

    /**
     * 过滤转换URL
     * @param string $url
     * @param string $domain
     * @return string
     */
    protected function filterListUrl(string $url, string $domain): string
    {
        if (str_starts_with($url, 'https://') || str_starts_with($url, 'http://')) {
            return $url;
        }

        $uri = $url ?: $this->pageUriBuilder($this->firstPage());
        return rtrim($domain, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * 种子列表页，第一页默认页码
     * @return int
     */
    public function firstPage(): int
    {
        return 1;
    }

    /**
     * 构造页面URI
     * @param int $page 页码
     * @param RouteEnum|string|null $route 路由实例或路由枚举值
     * @return string
     */
    public function pageUriBuilder(int $page, RouteEnum|string $route = null): string
    {
        $value = $route instanceof RouteEnum ? $route->value : $route;
        return str_replace('{page}', $page, $value ?: $this->getListDefaultRoute());
    }

    /**
     * 获取默认的列表路由规则
     * @return string
     */
    protected function getListDefaultRoute(): string
    {
        return RouteEnum::N3->value;
    }
}