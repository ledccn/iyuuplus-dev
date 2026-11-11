<?php

namespace Iyuu\SiteManager\Frameworks\FluxTorrent;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss as NexusPhpHasRss;
use Iyuu\SiteManager\Spider\RouteEnum;
use Throwable;

/**
 * FluxTorrent架构的RSS订阅与种子下载
 * - FluxTorrent按NexusPhp口径输出RSS（item含title/link/enclosure/guid/pubDate），
 *   因此列表解析整体复用NexusPhp的RSS解析器，本特质只覆盖与NexusPhp不同的两处：
 *   1. RSS路由：torrentrss.php?passkey= → /api/v1/rss/{订阅令牌}
 *   2. 种子下载：download.php?id=&passkey=（32位passkey）→ 兼容层 download.php?id=&token=
 * - 订阅令牌形如 fxr_ 加32位小写字母数字，同一个令牌既能读RSS也能下载种子
 */
trait HasRss
{
    use NexusPhpHasRss;

    /**
     * 获取默认的RSS路由规则
     * @return string
     */
    protected function getRssDefaultRoute(): string
    {
        if ($rss_url = $this->getConfig()->get('options.rss_url')) {
            return $rss_url;
        }

        return str_replace('{rsskey}', $this->getSubscriberToken(), RouteEnum::N15->value);
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
            $token = $this->getSubscriberToken();
            if ('' === $token) {
                throw new InvalidArgumentException('缺少FluxTorrent订阅令牌');
            }

            $domain = $this->getConfig()->parseDomain();
            $uri = strtr(RouteEnum::N16->value, [
                '{}' => $torrent->torrent_id,
                '{id}' => $torrent->torrent_id,
                '{token}' => $token
            ]);
            // 令牌随链接下发，下载种子不需要cookie
            $torrent->setDownload($domain . '/' . ltrim($uri, '/'), false);
            return $torrent->download;
        } catch (Error|Exception|Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * RSS模式：下载种子不需要cookie
     * @return bool
     */
    protected function isRssDownloadCookieRequired(): bool
    {
        return false;
    }

    /**
     * 获取FluxTorrent订阅令牌
     * - 优先 options.rsskey，兼容历史上填在 options.passkey 的令牌
     * @return string
     */
    protected function getSubscriberToken(): string
    {
        $token = (string)$this->getConfig()->get('options.rsskey', '');
        if ('' === $token) {
            $token = (string)$this->getConfig()->get('options.passkey', '');
        }

        return trim($token);
    }
}
