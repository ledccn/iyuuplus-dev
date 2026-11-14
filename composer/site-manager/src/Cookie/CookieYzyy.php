<?php

namespace Iyuu\SiteManager\Cookie;

use Error;
use Exception;
use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\HasRequestHtml;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use support\Log;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * yzyy（YzYY 站：Discuz + dz_seed 插件）
 * - 凭cookie解析种子列表页 torrents.php
 * - 列表行结构：<tr id="row_{tid}"> ... <td class="nfo"><div class="tt"><a title=主标题>
 *   ... <span class="sb">副标题</span> ... <a title="下载种子" href="...info_hash=...">
 */
class CookieYzyy extends BaseCookie
{
    use HasRequestHtml, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'yzyy';

    /**
     * 种子列表页路由
     */
    private const LIST_ROUTE = 'torrents.php?page={page}';

    /**
     * 契约方法：凭cookie解析HTML列表页
     * @param string $url 列表URL
     * @return array
     * @throws EmptyListException
     */
    public function process(string $url): array
    {
        $domain = $this->getConfig()->parseDomain();
        if ($url) {
            $target = preg_match('#^https?://#i', $url) ? $url : $domain . '/' . ltrim($url, '/');
        } else {
            $target = $domain . '/' . $this->pageUriBuilder($this->currentPage());
        }

        $html = $this->requestHtml($target);
        $crawler = new Crawler($html);
        $rows = $crawler->filterXPath("//tr[starts-with(@id,'row_')]");
        if (0 === $rows->count()) {
            if (str_contains($html, 'action=login') || str_contains($html, '登录')) {
                throw new EmptyListException('未登录或cookie已失效：' . $target);
            }
            throw new EmptyListException('页面解析失败：未找到种子行 ' . $target);
        }

        $items = [];
        foreach ($rows as $node) {
            try {
                $arr = $this->parseRow(new Crawler($node));
                if ($arr) {
                    $items[] = $arr;
                }
            } catch (Error|Exception|Throwable $throwable) {
                Log::error(__METHOD__ . ' 解析种子行异常：' . $throwable->getMessage() . ' 站点：yzyy');
            }
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败：种子行为空 ' . $target);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }

    /**
     * 解析单个种子行
     * @param Crawler $row
     * @return array
     */
    protected function parseRow(Crawler $row): array
    {
        $trId = (string)$row->attr('id');
        $id = (int)substr($trId, 4);
        if ($id <= 0) {
            return [];
        }

        $nfo = $row->filterXPath(".//td[contains(concat(' ', normalize-space(@class), ' '), ' nfo ')]");
        if (0 === $nfo->count()) {
            return [];
        }

        // 主标题 + 详情页
        $titleNode = $nfo->filterXPath(".//div[contains(concat(' ', normalize-space(@class), ' '), ' tt ')]/a")->first();
        if (0 === $titleNode->count()) {
            return [];
        }
        $h1 = trim((string)($titleNode->attr('title') ?: $titleNode->text('')));
        $details = trim((string)$titleNode->attr('href'));

        // 副标题（可能不存在）
        $title = '';
        $subNode = $nfo->filterXPath(".//div[contains(concat(' ', normalize-space(@class), ' '), ' sb ')]")->first();
        if ($subNode->count()) {
            $title = $this->normalizeWhitespace($subNode->text(''));
        }

        // 下载链接（列表行内 <a title="下载种子" href="...info_hash=...">）
        $download = '';
        $dlNode = $row->filterXPath(".//a[contains(@href,'info_hash=')]")->first();
        if ($dlNode->count()) {
            $download = str_replace('&amp;', '&', trim((string)$dlNode->attr('href')));
        }
        if ('' === $h1 || '' === $download) {
            return [];
        }

        // 促销类型：<span class="pp fr">Free</span> / <span class="pp x2">2*Free</span> 视为免费
        $rowHtml = $row->html();
        $isFree = str_contains($rowHtml, 'class="pp fr"') || str_contains($rowHtml, 'class="pp x2"');

        return [
            'id' => $id,
            'h1' => $h1,
            'title' => $h1 === $title ? '' : $title,
            'details' => $details,
            'download' => $download,
            'filename' => $id . '.torrent',
            'type' => $isFree ? 0 : 1,
        ];
    }

    /**
     * 种子列表页第一页页码
     */
    public function firstPage(): int
    {
        return 1;
    }

    /**
     * 构造页面URI
     * @param int $page 页码
     * @param RouteEnum|string|null $route 路由
     * @return string
     */
    public function pageUriBuilder(int $page, RouteEnum|string|null $route = null): string
    {
        $value = $route instanceof RouteEnum ? $route->value : $route;
        return str_replace('{page}', (string)$page, $value ?: self::LIST_ROUTE);
    }
}
