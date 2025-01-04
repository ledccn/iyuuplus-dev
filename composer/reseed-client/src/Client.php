<?php

namespace Iyuu\ReseedClient;

use Ledc\Curl\Curl;
use RuntimeException;

/**
 * 辅种客户端
 */
class Client extends AbstractCurl
{
    /**
     * 主域名
     */
    public const string BASE_API = 'http://2025.iyuu.cn';

    /**
     * 解析响应结果
     * @param Curl $curl
     * @param string $defaultMessage
     * @return array
     * @throws InternalServerErrorException
     */
    protected function parseResponse(Curl $curl, string $defaultMessage): array
    {
        if (!$curl->isSuccess()) {
            throw new InternalServerErrorException($curl->error_message ?? 'A服务器繁忙，请稍后再试 ' . $defaultMessage, 500);
        }

        $response = json_decode($curl->response, true);
        $code = $response['code'] ?? 403;
        $msg = $response['msg'] ?? $defaultMessage . ' B服务器繁忙，请稍后再试。';
        if ($code) {
            throw new RuntimeException($msg, $code);
        }

        return $response;
    }

    /**
     * 获取站点列表
     * @return array
     * @throws InternalServerErrorException
     */
    public function sites(): array
    {
        $curl = $this->getCurl()->get(self::BASE_API . '/reseed/sites/index');
        $response = $this->parseResponse($curl, '获取站点列表失败');

        return array_column($response['data']['sites'], null, 'site');
    }

    /**
     * 获取推荐站点列表
     * @return array
     * @throws InternalServerErrorException
     */
    public function recommend(): array
    {
        $curl = $this->getCurl()->get(self::BASE_API . '/reseed/sites/recommend');
        $response = $this->parseResponse($curl, '获取推荐站点列表');

        return $response['data'];
    }

    /**
     * 汇报持有的站点
     * @param array $data
     * @return string 站点哈希值（有效期7天，持有站点没变化时，不用重复获取）
     * @throws InternalServerErrorException
     */
    public function reportExisting(array $data): string
    {
        $curl = $this->getCurl()->post(self::BASE_API . '/reseed/sites/reportExisting', ['sid_list' => $data]);
        $response = $this->parseResponse($curl, '汇报持有的站点失败');
        if (empty($response['data']['sid_sha1'])) {
            throw new RuntimeException('返回值缺少sid_sha1字段');
        }

        return $response['data']['sid_sha1'];
    }

    /**
     * 获取可辅种数据
     * @param string $hash 种子info_hash
     * @param string $sha1 种子哈希值
     * @param string $sid_sha1 站点哈希值
     * @param string $version 版本号
     * @return array
     * @throws InternalServerErrorException
     */
    public function reseed(string $hash, string $sha1, string $sid_sha1, string $version): array
    {
        $data = [
            'hash' => $hash,
            'sha1' => $sha1,
            'sid_sha1' => $sid_sha1,
            'timestamp' => time(),
            'version' => $version,
        ];
        $curl = $this->getCurl()->post(self::BASE_API . '/reseed/index/index', $data);
        $response = $this->parseResponse($curl, '获取可辅种数据失败');
        return $response['data'];
    }

    /**
     * 查询单个种子的辅种数据
     * @param int $sid
     * @param int $torrent_id
     * @param string $sid_sha1
     * @param string $version
     * @return array
     * @throws InternalServerErrorException
     */
    public function single(int $sid, int $torrent_id, string $sid_sha1, string $version): array
    {
        $data = [
            'sid' => $sid,
            'torrent_id' => $torrent_id,
            'sid_sha1' => $sid_sha1,
            'timestamp' => time(),
            'version' => $version,
        ];
        $curl = $this->getCurl()->get(self::BASE_API . '/reseed/index/single', $data);
        $response = $this->parseResponse($curl, '获取可辅种数据失败');
        return $response['data'];
    }

    /**
     * 绑定合作站点
     * @param array $data
     * @return array
     * @throws InternalServerErrorException
     */
    public function bind(array $data): array
    {
        $curl = $this->getCurl()->post(self::BASE_API . '/reseed/users/bind', $data);
        return $this->parseResponse($curl, '绑定合作站点失败');
    }

    /**
     * 获取用户信息
     * @return array
     * @throws InternalServerErrorException
     */
    public function profile(): array
    {
        $curl = $this->getCurl()->get(self::BASE_API . '/reseed/users/profile');
        return $this->parseResponse($curl, '获取用户信息失败');
    }
}
