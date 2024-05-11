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
    protected const string BASE_API = 'https://dev.iyuu.cn';

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
            throw new InternalServerErrorException($curl->error_message ?? '服务器无响应 ' . $defaultMessage, 500);
        }

        $response = json_decode($curl->response, true);
        $code = $response['code'] ?? 403;
        $msg = $response['msg'] ?? $defaultMessage . ' 缺失错误信息';
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
     * 汇报持有的站点
     * @param array $data
     * @return string
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
}
