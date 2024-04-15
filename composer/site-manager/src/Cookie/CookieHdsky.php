<?php

namespace Iyuu\SiteManager\Cookie;

use Error;
use Exception;
use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasCookie;
use Iyuu\SiteManager\Spider\Pagination;
use Ledc\Curl\Curl;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * hdsky
 * - 凭cookie解析HTML列表页
 */
class CookieHdsky extends BaseCookie
{
    use HasCookie, Pagination;

    /**
     * 站点名称
     */
    public const SITE_NAME = 'hdsky';

    /**
     * 解析副标题节点值【Crawler】
     * @param Crawler $node
     * @return string
     */
    protected function parseTitleNode(Crawler $node): string
    {
        return $node->filterXPath('//td/span')->last()->text('');
    }

    /**
     * 认领种子的各种操作
     * @param int $torrent_id 种子id
     * @param string $action 动作
     * @param int $user_id 用户id
     * @return object
     */
    public function adoption(int $torrent_id, string $action = 'add', int $user_id = 0): object
    {
        $domain = $this->getConfig()->parseDomain();
        $curl = new Curl();
        $this->getConfig()->setCurlOptions($curl);
        $curl->setCookies($this->getConfig()->get('cookie'));
        $curl->post($domain . '/adoption.php', [
            'torrentid' => $torrent_id,
            'uid' => $user_id ?: $this->getConfig()->get('options.uid'),
            'action' => $action
        ]);
        if ($curl->isSuccess()) {
            /*$status = match ($result->success ?? '') {
                'added' => '认领种子成功',
                'deleted' => '取消认领成功',
                'maxreach' => '认领种子失败,您已达到最大认领数量。',
                default => false,
            };*/
            return json_decode($curl->response);
        }

        $this->baseDriver->throwException($curl);
    }

    /**
     * 获取当前做种列表
     * @param int $uid
     * @param string $type
     * @return array
     */
    public function getUserTorrentList(int $uid, string $type = 'seeding'): array
    {
        $domain = $this->getConfig()->parseDomain();
        $curl = new Curl();
        $this->getConfig()->setCurlOptions($curl);
        $curl->setCookies($this->getConfig()->get('cookie'));
        $curl->get($domain . '/getusertorrentlistajax.php', [
            'userid' => $uid,
            'type' => $type
        ]);
        if ($curl->isSuccess()) {
            $html = $curl->response;
            $result = [];
            try {
                $crawler = new Crawler($html);
                $list = $crawler->filterXPath('//table//tr');
                $list->each(function (Crawler $node, $i) use (&$result, $domain) {
                    if ($i) {
                        $row = [];
                        $node->filterXPath('//td')->each(function (Crawler $node_td, $ii) use (&$row, $domain) {
                            $key = match ($ii) {
                                0 => '类型',
                                1 => '标题',
                                2 => '大小',
                                3 => '种子数',
                                4 => '下载数',
                                5 => '上传',
                                6 => '下载',
                                7 => '分享率',
                                default => 'default',
                            };

                            $row[$key] = match ($ii) {
                                0 => $node_td->filterXPath('//img')->attr('alt'),
                                1, 2, 3, 4, 5, 6, 7 => $node_td->text(),
                                default => 'default',
                            };
                            if (1 === $ii) {
                                $details = $node_td->filterXPath('//a')->attr('href');
                                $row['详情页'] = $domain . '/' . $details;
                                if (preg_match('/details.php\?id=(\d+)/i', $details, $matches)) {
                                    $row['torrent_id'] = $matches[1];
                                }
                            }
                        });
                        $result[] = $row;
                    }
                });
            } catch (Error|Exception|Throwable $throwable) {
                throw new RuntimeException($throwable->getMessage(), $throwable->getCode());
            }

            file_put_contents(runtime_path('hdsky.json'), json_encode($result, JSON_UNESCAPED_UNICODE));
            file_put_contents(runtime_path('hdsky_tid.json'), json_encode(array_column($result, 'torrent_id'), JSON_UNESCAPED_UNICODE));
            return $result;
        }

        $this->baseDriver->throwException($curl);
    }
}
