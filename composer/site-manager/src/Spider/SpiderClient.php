<?php

namespace Iyuu\SiteManager\Spider;

use Iyuu\SiteManager\Exception\BadRequestException;
use Ledc\Container\App;
use Ledc\Curl\Curl;
use RuntimeException;

/**
 * 分布式爬虫客户端
 */
class SpiderClient
{
    /**
     * 爬虫服务主域名
     */
    const string SPIDER_HOST = 'http://api.iyuu.cn:2120';
    /**
     * 站点列表
     */
    const string API_SITE_LIST = self::SPIDER_HOST . '/spider/site/index';
    /**
     * 创建
     */
    const string API_SPIDER_CREATE = self::SPIDER_HOST . '/spider/torrent/create';
    /**
     * 查重
     */
    const string API_SPIDER_FIND = self::SPIDER_HOST . '/spider/torrent/find';
    /**
     * 应用id
     * @var string
     */
    protected string $appid;
    /**
     * 上报密钥
     * @var string
     */
    protected string $secret;
    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * 构造函数
     * @param string $appid 爱语飞飞token
     * @param string $secret 上报密钥
     */
    public function __construct(string $appid, string $secret)
    {
        $this->appid = $appid;
        $this->secret = $secret;
        $this->curl = new Curl();
        $this->curl->setTimeout(8, 8);
    }

    /**
     * 获取爬虫客户端（单例）
     * @return self
     */
    public static function getInstance(): self
    {
        return App::pull(static::class, [getenv('IYUU_APPID') ?: '', getenv('IYUU_SECRET') ?: '']);
    }

    /**
     * 站点列表
     * @return array
     * @throws BadRequestException
     */
    public function siteList(): array
    {
        $data = [];
        if (!$this->isAdmin()) {
            $data['appid'] = $this->appid;
        }
        $now = time();
        $data['timestamp'] = $now;
        $signature = sha1($now . $this->secret);
        $data['sign'] = $signature;
        $res = $this->curl->get(static::API_SITE_LIST, $data);
        if (!$res->isSuccess()) {
            $err_msg = $this->formatErrorMessage($res);
            throw new BadRequestException('获取站点列表失败：' . $err_msg);
        }
        $response = json_decode($res->response, true);
        $code = $response['code'] ?? -1;
        $msg = $response['msg'] ?? '缺失错误信息';
        if (200 === $code) {
            return array_column($response['data']['sites'], null, 'site');
        } else {
            throw new RuntimeException($msg);
        }
    }

    /**
     * 查重
     * @param string $site 站点名称
     * @param int $torrent_id 种子ID
     * @return void
     * @throws BadRequestException
     */
    public function findTorrent(string $site, int $torrent_id): void
    {
        $data = [
            'site' => $site,
            'torrent_id' => $torrent_id,
        ];

        $retry = 3;
        do {
            $res = $this->curl->get(static::API_SPIDER_FIND, $data);
            if (!$res->isSuccess()) {
                var_dump($res);
                $err_msg = $this->formatErrorMessage($res);
                if ($retry <= 0) {
                    throw new BadRequestException('查重失败：' . $err_msg);
                }
            }
        } while (!$res->isSuccess() && $retry--);

        $response = json_decode($res->response, true);
        //var_dump($response);
        $code = $response['code'] ?? -1;
        $msg = $response['msg'] ?? '缺失错误信息';
        if (200 === $code) {
            // 服务器不存在该种子，符合下载条件
            return;
        }
        throw match ($code) {
            202 => new RuntimeException('-----种子：在远端服务器已存在！！！'),
            405 => new RuntimeException('-----种子：' . $msg),
            default => new BadRequestException('-----错误消息：' . $msg . PHP_EOL),
        };
    }

    /**
     * @param Curl $curl
     * @return string
     */
    public function formatErrorMessage(Curl $curl): string
    {
        return $curl->error_message ?? '服务器无响应';
    }

    /**
     * 创建
     * @param string $site
     * @param SpiderTorrents $torrent
     * @param array $data
     * @return void
     * @throws BadRequestException
     */
    public function createTorrent(string $site, SpiderTorrents $torrent, array $data): void
    {
        //Step1：组装上报数据
        $data['site'] = $site;
        $data['torrent_id'] = $torrent->id;
        // 特殊字段：种子分组ID【海豚、海报、皮等特有字段】
        if ($group_id = $torrent->group_id) {
            $data['group_id'] = $group_id;
        }
        $data['h1'] = $torrent->h1 ?? '';
        $data['title'] = $torrent->title ?? '';

        $retry = 3;
        do {
            $now = time();
            $data['timestamp'] = $now;
            //Step2：非超级管理员的时候，添加appid参数，验证用户站点上传权限
            if (!$this->isAdmin()) {
                $data['appid'] = $this->appid;
            }
            //Step3：简单签名 sha1(timestamp + secret)
            $signature = sha1($now . $this->secret);
            $data['sign'] = $signature;

            $res = $this->curl->post(static::API_SPIDER_CREATE, $data);
            if (!$res->isSuccess()) {
                var_dump($res);
                $err_msg = $this->formatErrorMessage($res);
                if ($retry <= 0) {
                    throw new BadRequestException('特征码上报失败：' . $err_msg);
                }
            }
        } while (!$res->isSuccess() && $retry--);

        $response = json_decode($res->response, true);
        //var_dump($response);
        $code = $response['code'] ?? -1;
        $msg = $response['msg'] ?? '缺失错误信息';
        if (200 !== $code) {
            throw new BadRequestException('-----错误消息：' . $msg . PHP_EOL);
        }

        echo $site . '种子特征码上报成功。' . $msg . PHP_EOL . PHP_EOL;
    }

    /**
     * 是否超级管理员
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return !(ctype_digit($this->appid) && 14 === strlen($this->appid));
    }
}
