<?php

namespace Iyuu\SiteManager\Driver;

use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Ledc\Curl\Curl;
use stdClass;
use Throwable;

/**
 * piggo：令牌 + download_page JSON POST 换取种子链接
 */
class DriverPiggo extends BaseDriver implements Processor, ProcessorXml
{
    use HasRss;

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'piggo';

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
        if ($this->getToken() === '') {
            throw new InvalidArgumentException('piggo 缺少令牌');
        }
    }

    /**
     * 站点存取令牌
     * @return string
     */
    protected function getToken(): string
    {
        return trim((string)$this->getConfig()->getOptions('token', ''));
    }

    /**
     * 设置 Curl 令牌与 JSON 请求头
     * @param Curl $curl
     * @return Curl
     */
    protected function setCurlToken(Curl $curl): Curl
    {
        return $curl
            ->setHeader('Authorization', 'Bearer ' . $this->getToken())
            ->setHeader('Accept', 'application/json')
            ->setHeader('Content-Type', 'application/json');
    }

    /**
     * 生成下载种子的完整 URL（先 POST download_page 取 data.url）
     * @param Torrent $torrent
     * @return string
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $domain = $this->getConfig()->parseDomain();
            $uri = strtr($this->getConfig()->parseUri(), $this->parseReplace($torrent));
            if ($uri === '') {
                throw new InvalidArgumentException('piggo 未配置 download_page');
            }
            $url = $domain . '/' . ltrim($uri, '/');

            $curl = new Curl();
            $this->getConfig()->setCurlOptions($curl);
            $this->setCurlToken($curl);
            $curl->post($url, new stdClass(), true);
            if (!$curl->isSuccess()) {
                $this->throwException($curl);
            }

            $result = json_decode((string)$curl->response);
            if (!is_object($result)) {
                throw new InvalidArgumentException('获取 piggo 种子链接失败：响应不是 JSON 对象');
            }

            $code = $result->code ?? null;
            $downloadUrl = '';
            if (isset($result->data) && is_object($result->data)) {
                $downloadUrl = (string)($result->data->url ?? '');
            }
            if ((int)$code === 0 && $this->isHttpUrl($downloadUrl)) {
                $torrent->setDownload($downloadUrl, false);
                return $torrent->download;
            }

            $msg = (string)($result->msg ?? $result->message ?? '');
            throw new InvalidArgumentException(
                $msg !== '' ? ('获取 piggo 种子链接失败：' . $msg) : '获取 piggo 种子链接失败'
            );
        } catch (TorrentException $e) {
            throw $e;
        } catch (Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), (int)$throwable->getCode());
        }
    }

    /**
     * 仅允许 http(s) 种子地址
     * @param string $url
     * @return bool
     */
    protected function isHttpUrl(string $url): bool
    {
        if ($url === '' || strlen($url) > 4096) {
            return false;
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * 下载真实种子文件前跟随跳转
     * @param Curl $curl
     * @return void
     */
    protected function beforeDownload(Curl $curl): void
    {
        parent::beforeDownload($curl);
        $curl->setFollowLocation(1);
    }

    /**
     * 解析 download_page 占位符
     * @param Torrent $torrent
     * @return array
     */
    protected function parseReplace(Torrent $torrent): array
    {
        return [
            '{}' => $torrent->torrent_id,
            '{id}' => $torrent->torrent_id,
            '{token}' => $this->getToken(),
        ];
    }

    /**
     * 获取默认的 RSS 路由（仅使用配置的 rss_url）
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        return (string)$this->getConfig()->get('options.rss_url', '');
    }
}
