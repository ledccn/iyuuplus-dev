<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\Yemapt\HasOpenApi;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use InvalidArgumentException;
use RuntimeException;

/**
 * yemapt
 * - 凭AuthKey请求开放API种子列表
 */
class CookieYemapt extends BaseCookie
{
    use HasOpenApi, Pagination;

    /**
     * 种子列表API
     */
    private const string LIST_API = 'openApi/torrent/fetchOpenTorrentList.json';

    /**
     * 列表每页数量
     */
    private const int PAGE_SIZE = 40;

    /**
     * 当前处理的是否为最后一页
     */
    private bool $lastPage = false;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'yemapt';

    /**
     * 凭AuthKey请求开放API并解析种子
     * @param string $url
     * @return array
     * @throws EmptyListException
     */
    public function process(string $url): array
    {
        // 上一页不足 PAGE_SIZE 时，不再继续请求后续页面。
        if ($this->lastPage) {
            return [];
        }

        $domain = rtrim($this->getConfig()->parseDomain(), '/');
        $page = $this->parsePage($url);
        $torrents = $this->requestList($page);
        if (!is_array($torrents)) {
            throw new RuntimeException('野马PT种子列表接口data字段格式异常');
        }
        $this->lastPage = count($torrents) < self::PAGE_SIZE;

        // 空页是正常的列表结束状态，不以异常中断爬虫命令。
        if ([] === $torrents) {
            return [];
        }

        $items = [];
        foreach ($torrents as $torrent) {
            if (!is_array($torrent) || empty($torrent['id'])) {
                continue;
            }

            $id = (int)$torrent['id'];
            $length = (int)($torrent['fileSize'] ?? 0);
            $items[] = [
                'id' => $id,
                'h1' => (string)($torrent['showName'] ?? ''),
                'title' => (string)($torrent['shortDesc'] ?? ''),
                'details' => $domain . '/#/torrent/detail/' . $id . '/',
                'download' => '',
                'filename' => $id . '.torrent',
                'type' => 'free' === ($torrent['downloadPromotion'] ?? '') ? 0 : 1,
                'time' => (string)($torrent['listingTime'] ?? ''),
                'size' => Utils::dataSize($length),
                'length' => $length,
            ];
        }

        if (empty($items)) {
            throw new EmptyListException("野马PT第{$page}页没有可用的种子数据");
        }

        SpiderTorrents::notify($items, $this->baseDriver, false);
        return $items;
    }

    /**
     * 野马PT开放API只接受AuthKey，不接受Cookie登录凭据
     */
    protected function validateConfig(): void
    {
        if (empty($this->getConfig()->getOptions('authkey'))) {
            throw new InvalidArgumentException('野马PT缺少AuthKey配置');
        }
    }

    /**
     * 下载凭证由开放API生成，不需要Cookie
     */
    protected function isSpiderDownloadCookieRequired(): bool
    {
        return false;
    }

    /**
     * 请求种子列表API
     */
    protected function requestList(int $page): mixed
    {
        return $this->postOpenApi(self::LIST_API, [
            'keyword' => '',
            'pageParam' => [
                'current' => $page,
                'pageSize' => self::PAGE_SIZE,
            ],
            'sorter' => [
                'field' => 'listingTime',
                'order' => 'descend',
            ],
        ]);
    }

    /**
     * 当前已处理页面是否为最后一页
     */
    public function isLastPage(): bool
    {
        return $this->lastPage;
    }

    /**
     * 从分页URI提取页码
     */
    private function parsePage(string $url): int
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            if (isset($params['page']) && is_numeric($params['page'])) {
                return max(1, (int)$params['page']);
            }
        }

        return $this->firstPage();
    }

    /**
     * 构造分页URI；page参数仅用于向process传递页码
     */
    public function pageUriBuilder(int $page, RouteEnum|string $route = null): string
    {
        $value = $route instanceof RouteEnum ? $route->value : $route;
        return str_replace('{page}', max(1, $page), $value ?: self::LIST_API . '?page={page}');
    }

    /**
     * API页码从1开始
     */
    public function firstPage(): int
    {
        return 1;
    }

    /**
     * 默认抓取最新3页（最多120条）
     */
    public function crontabEndPage(): int
    {
        return 3;
    }
}
