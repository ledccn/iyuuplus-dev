<?php

namespace Iyuu\SiteManager;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\DownloaderInterface;
use Iyuu\SiteManager\Contracts\DownloaderLinkInterface;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\Response;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Ledc\Curl\Curl;
use RuntimeException;
use Throwable;
use Webman\Event\Event;

/**
 * 站点基础类
 */
abstract class BaseDriver implements DownloaderInterface, DownloaderLinkInterface
{
    /**
     * 当前站点配置
     * @var Config
     */
    protected readonly Config $config;

    /**
     * @param array $config 当前站点配置
     */
    final public function __construct(array $config)
    {
        $this->config = new Config($config);
        $this->initialize();
    }

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * 获取爬虫实例：凭cookie解析HTML列表页
     * @return BaseCookie
     */
    final public function makeBaseCookie(): BaseCookie
    {
        $site = $this->config->site;
        $class = BaseCookie::siteToClass($site);
        if (is_subclass_of($class, BaseCookie::class)) {
            return new $class($this);
        }

        throw new InvalidArgumentException("Cookie Processor [$site] not supported.");
    }

    /**
     * 凭cookie解析HTML列表页
     * @param string $url
     * @return array
     * @throws EmptyListException
     */
    final public function process(string $url): array
    {
        if (!$this instanceof Processor) {
            throw new RuntimeException(get_class($this) . '未实现HTML解析接口：' . Processor::class);
        }
        return $this->makeBaseCookie()->process($url);
    }

    /**
     * 获取当前站点配置
     * @return Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * RSS模式：是否必须cookie才能下载种子
     * @return bool
     */
    protected function isRssDownloadCookieRequired(): bool
    {
        return false;
    }

    /**
     * 生成下载种子的完整的URL
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $domain = $this->getConfig()->parseDomain();
            $uri = $this->getConfig()->parseUri();
            $url_replace = $this->parseReplace($torrent);
            $url_join = $this->getConfig()->parseJoinQueryString();
            if ($url_join) {
                $delimiter = str_contains($uri, '?') ? '&' : '?';
                $url_join = $delimiter . $url_join;
            }
            $uri = strtr($uri, $url_replace);

            $torrent->setDownload($domain . '/' . $uri . $url_join, $this->getConfig()->isCookieRequired());
            return $torrent->download;
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 下载种子
     * - 可能需要构造种子下载链接
     * @param Torrent $torrent
     * @return Response
     * @throws TorrentException
     */
    public function download(Torrent $torrent): Response
    {
        try {
            $curl = new Curl();
            $this->beforeDownload($curl);
            $this->doDownload($curl, $torrent);
            $this->afterDownload($curl, $torrent);
            // 解析响应结果
            if ($curl->isSuccess()) {
                return new Response($curl->response, true);
            }

            $this->throwException($curl);
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 下载种子（爬虫模式或RSS模式）
     * - 无需构造种子下载链接
     * @param SpiderTorrents $spiderTorrents
     * @return string|bool
     * @throws TorrentException
     */
    public function downloadMetadata(SpiderTorrents $spiderTorrents): string|bool
    {
        try {
            $curl = new Curl();
            $this->beforeDownload($curl);
            $this->doDownloadMetadata($curl, $spiderTorrents);
            $this->afterDownload($curl, $spiderTorrents);
            // 解析响应结果
            if ($curl->isSuccess()) {
                return $curl->response;
            }

            $this->throwException($curl);
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 向种子链接，发起请求
     * @param Curl $curl
     * @param SpiderTorrents $spiderTorrents
     * @return void
     */
    protected function doDownloadMetadata(Curl $curl, SpiderTorrents $spiderTorrents): void
    {
        if ($spiderTorrents->isCookieRequired()) {
            $curl->setCookies($this->getConfig()->get('cookie'));
        }
        $curl->get($spiderTorrents->download);
    }

    /**
     * 请求下载种子前回调
     * @param Curl $curl
     * @return void
     */
    protected function beforeDownload(Curl $curl): void
    {
        $this->config->setCurlOptions($curl);
    }

    /**
     * 向种子链接，发起请求
     * @param Curl $curl
     * @param Torrent $torrent
     * @return void
     * @throws TorrentException
     */
    protected function doDownload(Curl $curl, Torrent $torrent): void
    {
        $url = $torrent->download ?: $this->downloadLink($torrent);
        if (empty($torrent->download) || $torrent->downloadCookieRequired) {
            $curl->setCookies($this->getConfig()->get('cookie'));
        }

        $curl->get($url);
    }

    /**
     * 请求下载种子后回调
     * @param Curl $curl
     * @param Torrent|SpiderTorrents $torrent
     * @return void
     */
    protected function afterDownload(Curl $curl, Torrent|SpiderTorrents $torrent): void
    {
        Event::emit('download.torrent.after', [$curl, $torrent, $this]);
    }

    /**
     * 请求失败，抛出异常
     * @param Curl $curl
     * @return void
     * @access public
     */
    public function throwException(Curl $curl): void
    {
        $errmsg = $curl->error_message ?: 'error_message为空';
        if (302 === $curl->http_status_code) {
            if ($this->getConfig()->isCookieRequired()) {
                $errmsg .= ' Cookie过期';
            } else {
                $errmsg .= ' 密钥或凭据过期';
            }
        }

        throw new RuntimeException('请求错误：' . $errmsg);
    }

    /**
     * 解析生成替换规则
     * @param Torrent $torrent
     * @return array
     */
    protected function parseReplace(Torrent $torrent): array
    {
        return [
            '{}' => $torrent->torrent_id,
            '{id}' => $torrent->torrent_id,
            '{passkey}' => $this->getConfig()->get('options.passkey', '')
        ];
    }
}
