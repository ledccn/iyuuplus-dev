<?php

namespace Iyuu\SiteManager\Frameworks\NexusPhp;

use Error;
use Exception;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestHtml;
use Iyuu\SiteManager\Library\Selector;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use support\Log;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * 爬虫框架，NexusPHP
 */
trait HasCookie
{
    use HasRequestHtml;

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
        $list = Selector::select($html, "//table[contains(@class,'torrentname')]");
        if (empty($list)) {
            throw new EmptyListException('页面解析失败A' . PHP_EOL . $html);
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

                    try {
                        // 副标题
                        $arr['title'] = $this->normalizeWhitespace($this->parseTitleNode(new Crawler($v)));
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
                    // 存活时间
                    // 大小
                    // 种子数
                    // 下载数
                    // 完成数
                    // 完成进度

                    // 调试当前站点
                    if ($this->isDebugCurrent()) {
                        var_dump_exit($arr);
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
     * 种子列表页，第一页默认页码
     * @return int
     */
    public function firstPage(): int
    {
        return 0;
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
        return RouteEnum::N1->value;
    }
}
