<?php

namespace Iyuu\SiteManager\Cookie;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Ledc\Curl\Curl;
use RuntimeException;

/**
 * zhuque
 * - 凭cookie解析HTML列表页
 */
class CookieZhuque extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'zhuque';

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
        $response = $this->requestHtml($this->filterListUrl($url, $domain));
        $result = json_decode($response, true);
        $status = $result['status'] ?? 0;
        $data = $result['data'] ?? [];
        if (empty($data) || 200 !== $status) {
            throw new EmptyListException('页面解析失败A');
        }

        $torrents = $data['torrents'] ?? [];
        if (empty($torrents)) {
            throw new EmptyListException('页面解析失败B');
        }

        $items = [];
        foreach ($torrents as $v) {
            $arr = [];
            $torrent_id = $v['id'];
            $arr['id'] = $torrent_id;
            $arr['h1'] = $v['title'] ?? '';
            $arr['title'] = $v['subtitle'] ?? '';
            $arr['details'] = $host . str_replace('{}', $torrent_id, $this->getConfig()->parseDetailUri());

            $replace = [
                '{}' => $torrent_id,
                '{torrent_key}' => $this->getConfig()->getOptions('torrent_key'),
            ];
            $arr['download'] = $host . strtr($this->getConfig()->parseUri(), $replace);
            $arr['filename'] = $torrent_id . '.torrent';
            $arr['type'] = 1;
            $items[] = $arr;
        }

        if (empty($items)) {
            throw new EmptyListException('页面解析失败C' . PHP_EOL);
        }

        SpiderTorrents::notify($items, $this->baseDriver, $this->isSpiderDownloadCookieRequired());
        return $items;
    }

    /**
     * 请求html页面
     * @param string $url
     * @return string
     */
    protected function requestHtml(string $url): string
    {
        $data = [];
        $parse = parse_url($url);
        parse_str($parse['query'], $data);

        $curl = new Curl();
        $config = $this->getConfig();
        $config->setCurlOptions($curl);
        $curl->setCookies($config->get('cookie', $config->get('cookies', '')));
        $this->setCurlXCsrfToken($curl);
        $curl->post($url, $data);
        if (!$curl->isSuccess()) {
            $errmsg = $curl->error_message ?? '网络不通或cookies过期';
            throw new RuntimeException('下载HTML失败：' . $errmsg);
        }

        $html = $curl->response;
        if (is_bool($html) || empty($html)) {
            throw new RuntimeException('下载HTML失败：curl_exec返回错误');
        }
        return $html;
    }

    /**
     * 设置Curl的 X-Csrf-Token
     * @param Curl $curl
     * @return Curl
     */
    public function setCurlXCsrfToken(Curl $curl): Curl
    {
        return $curl->setHeader('X-Csrf-Token', $this->getConfig()->getOptions('x_csrf_token'));
    }

    /**
     * 获取默认的列表路由规则
     * @return string
     */
    protected function getListDefaultRoute(): string
    {
        return RouteEnum::N12->value;
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
}
