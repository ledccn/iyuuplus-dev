<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestHtml;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use Symfony\Component\DomCrawler\Crawler;

/**
 * greatposterwall
 * - 凭cookie解析HTML列表页
 */
class CookieGreatposterwall extends BaseCookie
{
    use HasRequestHtml, Pagination;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'greatposterwall';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return false;
    }

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
        $list = Selector::select($html, "//table[contains(@id,'torrent_table')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL . $html);
        }
        $links = Selector::select($list, "//a/@href");

        $items = [];
        foreach ($links as $v) {
            $url = str_replace('&amp;', '&', $v);
            if (str_contains($url, 'usetoken=1')) {
                continue;
            }

            $arr = [];
            $matches = [];
            if (preg_match('/torrents.php\?action=download&id=(?<id>\d+)&authkey=([a-zA-Z0-9]+)&torrent_pass=([a-zA-Z0-9]+)/i', $url, $matches)) {
                $details = $matches[0];
                $arr['id'] = $matches['id'];
                $arr['h1'] = '';
                $arr['title'] = '';
                // 详情页
                $arr['details'] = $host . $details;
                // 下载地址
                $arr['download'] = $host . $url;
                // 文件名
                $arr['filename'] = $arr['id'] . '.torrent';
                // 免费种子
                $arr['type'] = 0;
                // 调试当前站点
                if ($this->isDebugCurrent()) {
                    var_dump_exit($arr);
                }
                $items[] = $arr;
            }
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败B' . PHP_EOL . $html);
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

    /**
     * 获取主标题
     * @param Crawler|string $node
     * @return string
     */
    protected function parseH1Node(Crawler|string $node): string
    {
        if (preg_match('/<a.*? title=[\'|\"](?<h1>.*?)[\'|\"]/i', $node, $matches_h1)) {
            return $matches_h1['h1'];
        } else {
            return '';
        }
    }

    /**
     * 解析副标题节点值
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        $first = $node->filterXPath('//td')->first();
        $temp = explode('<br>', $first->html());
        return count($temp) === 2 ? Utils::regexRemove('#.*</span>#ims', $temp[1]) : '';
    }

    /**
     * 过滤转换URL
     * @param string $url
     * @param string $domain
     * @return string
     */
    protected function filterListUrl(string $url, string $domain): string
    {
        $uri = $url ?: $this->pageUriBuilder($this->firstPage());
        Utils::removeSchemeHost($url);
        return rtrim($domain, '/') . '/' . ltrim($uri, '/');
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
     * 种子列表页，第一页默认页码
     * @return int
     */
    public function firstPage(): int
    {
        return 1;
    }


    /**
     * 爬虫的周期性定时任务，结束页码
     * - 设置uri时，仅爬取uri指定的单页
     * - 不设置uri时，才能使用当前方法
     * @return int
     */
    public function crontabEndPage(): int
    {
        return 3;
    }

    /**
     * 获取默认的列表路由规则
     * @return string
     */
    protected function getListDefaultRoute(): string
    {
        return RouteEnum::N14->value;
    }
}
