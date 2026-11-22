<?php

namespace Iyuu\SiteManager\Driver;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Utils;
use Ledc\Curl\Curl;
use RuntimeException;
use Throwable;

/**
 * colapt（可乐PT）：开放 API（JSON + X-API-Key 请求头）
 * - 种子列表：GET api/v1/media?view=torrents（listMedia，Key 需「读取」范围）
 * - 下载：POST api/v1/torrents/{id}/download-links（createDownloadLink，Key 需「下载」范围）
 *   返回 30 分钟有效的签名地址，再不带 Key 直接 GET，200 返回种子文件，不跳转
 * - 文档：https://www.colapt.com/api-docs
 */
class DriverColapt extends BaseDriver implements ProcessorXml
{
    /**
     * 站点名称
     */
    public const string SITE_NAME = 'colapt';

    /**
     * 开放 API 前缀
     */
    public const string API_PREFIX = 'api/v1/';

    /**
     * 默认的种子列表路由（最新发布的种子，每页50条）
     */
    public const string LIST_ROUTE = 'api/v1/media?view=torrents&sort=latest&page_size=50';

    /**
     * 默认的下载链接路由（站点未配置 download_page 时使用）
     */
    public const string DOWNLOAD_LINK_ROUTE = 'api/v1/torrents/{id}/download-links';

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
        if ($this->getApiKey() === '') {
            throw new InvalidArgumentException('可乐PT 缺少 API Key（账号设置 → API Key，范围勾选 读取 + 下载）');
        }
    }

    /**
     * 开放 API 的 Key
     * @return string
     */
    protected function getApiKey(): string
    {
        return trim((string)$this->getConfig()->getOptions('x_api_key', ''));
    }

    /**
     * 创建带 X-API-Key 请求头的 Curl
     * @return Curl
     */
    protected function apiCurl(): Curl
    {
        $curl = new Curl();
        $this->getConfig()->setCurlOptions($curl);
        return $curl->setHeader('X-API-Key', $this->getApiKey())
            ->setHeader('Accept', 'application/json');
    }

    /**
     * 相对站点域名的地址转为完整URL
     * @param string $uri
     * @return string
     */
    protected function absoluteUrl(string $uri): string
    {
        if (str_starts_with($uri, 'https://') || str_starts_with($uri, 'http://')) {
            return $uri;
        }
        return $this->getConfig()->parseDomain() . '/' . ltrim($uri, '/');
    }

    /**
     * 解析开放 API 的 JSON 响应，失败时抛出站点给出的错误
     * @param Curl $curl
     * @param string $action
     * @return array
     */
    protected function parseApiResponse(Curl $curl, string $action): array
    {
        $result = json_decode((string)$curl->response, true);
        if ($curl->isSuccess() && is_array($result)) {
            return $result;
        }
        $message = is_array($result) ? (string)($result['error']['message'] ?? '') : '';
        $code = is_array($result) ? (string)($result['error']['code'] ?? '') : '';
        if ($message !== '') {
            throw new RuntimeException("可乐PT {$action}失败：HTTP {$curl->http_status_code} {$code} {$message}");
        }
        $this->throwException($curl);
        throw new RuntimeException("可乐PT {$action}失败");
    }

    /**
     * 契约方法：读取最新种子列表（用开放 API 的 JSON 列表 listMedia 代替 RSS）
     * @param string $url 列表地址，留空时使用默认路由
     * @return array
     * @throws EmptyListException
     */
    public function processXml(string $url): array
    {
        $url = $this->absoluteUrl($url ?: self::LIST_ROUTE);
        $curl = $this->apiCurl();
        $curl->get($url);
        $page = $this->parseApiResponse($curl, '获取种子列表');

        $domain = $this->getConfig()->parseDomain();
        $items = [];
        foreach ($page['items'] ?? [] as $group) {
            foreach ($group['versions'] ?? [] as $version) {
                $id = (int)($version['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $length = (string)($version['size_bytes'] ?? '0');
                $published = $version['published_at'] ?? null;
                $promotion = $version['promotion'] ?? null;
                $free = is_array($promotion) && 0 === (int)($promotion['download']['num'] ?? 1);

                $torrent = [];
                $torrent['id'] = $id;
                $torrent['h1'] = (string)($version['release_name'] ?? '');
                $torrent['title'] = (string)($group['media']['title'] ?? '');
                $torrent['details'] = $domain . '/torrents/' . $id;
                // 下载地址需要先换取签名链接，见 doDownloadMetadata
                $torrent['download'] = '';
                $torrent['filename'] = $id . '.torrent';
                $torrent['type'] = $free ? 0 : 1;   // 免费0
                $torrent['time'] = date('Y-m-d H:i:s', $published ? strtotime($published) : time());
                $torrent['size'] = Utils::dataSize($length);
                $torrent['length'] = $length;
                $torrent['guid'] = $torrent['details'];
                $items[] = $torrent;
            }
        }

        if (empty($items)) {
            throw new EmptyListException('可乐PT 种子列表为空' . PHP_EOL . $curl->response);
        }
        SpiderTorrents::notify($items, $this, false);
        return $items;
    }

    /**
     * 生成下载种子的完整的URL：调用 createDownloadLink 换取签名下载地址
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $url = $this->createDownloadLink($torrent->torrent_id);
            $torrent->setDownload($url, false);
            return $torrent->download;
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), (int)$throwable->getCode());
        }
    }

    /**
     * 换取种子的签名下载地址（30分钟有效，下载时不需要 Key）
     * @param int $torrentId
     * @return string
     */
    protected function createDownloadLink(int $torrentId): string
    {
        $uri = $this->getConfig()->parseUri() ?: self::DOWNLOAD_LINK_ROUTE;
        $uri = strtr($uri, ['{}' => $torrentId, '{id}' => $torrentId]);
        $curl = $this->apiCurl();
        $curl->post($this->absoluteUrl($uri));
        $result = $this->parseApiResponse($curl, '获取种子下载链接');

        $downloadUrl = (string)($result['download_url'] ?? '');
        if ($downloadUrl === '') {
            throw new RuntimeException('可乐PT 获取种子下载链接失败：响应缺少 download_url');
        }
        return $this->absoluteUrl($downloadUrl);
    }

    /**
     * 爬虫/上报模式下载种子：列表里没有下载地址，先换取签名链接
     * @param Curl $curl
     * @param SpiderTorrents $spiderTorrents
     * @return void
     */
    protected function doDownloadMetadata(Curl $curl, SpiderTorrents $spiderTorrents): void
    {
        $url = $spiderTorrents->download ?: $this->createDownloadLink((int)$spiderTorrents->id);
        $curl->get($url);
    }
}
