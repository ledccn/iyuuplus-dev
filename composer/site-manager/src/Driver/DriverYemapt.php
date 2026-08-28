<?php

namespace Iyuu\SiteManager\Driver;

use InvalidArgumentException;
use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Processor;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Contracts\Torrent;
use Iyuu\SiteManager\Exception\TorrentException;
use Iyuu\SiteManager\Frameworks\NexusPhp\HasRss;
use Iyuu\SiteManager\Frameworks\Yemapt\HasOpenApi;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Ledc\Curl\Curl;
use RuntimeException;

/**
 * yemapt
 */
class DriverYemapt extends BaseDriver implements Processor, ProcessorXml
{
    use HasOpenApi, HasRss;

    /**
     * 获取API用户基本信息
     */
    private const string BASIC_INFO_API = 'openApi/user/fetchBasicInfo.json';

    /**
     * 根据piecesHash批量反查种子ID
     */
    private const string FETCH_ID_BY_PIECES_HASH_API = 'openApi/torrent/fetchTorrentIdWithPiecesHash.json';

    /**
     * 生成临时种子下载凭证
     */
    private const string GENERATE_DOWNLOAD_KEY_API = 'openApi/torrent/generateDownloadKey.json';

    /**
     * 使用临时凭证下载种子
     */
    private const string DOWNLOAD_API = 'api/torrent/download1';

    /**
     * 站点名称
     */
    public const string SITE_NAME = 'yemapt';

    /**
     * 校验野马PT开放API配置
     */
    protected function initialize(): void
    {
        if (empty($this->getConfig()->getOptions('authkey'))) {
            throw new InvalidArgumentException('野马PT缺少AuthKey配置');
        }
    }

    /**
     * 验证AuthKey并获取当前API用户基本信息
     */
    public function fetchBasicInfo(): array
    {
        $data = $this->postOpenApi(self::BASIC_INFO_API);
        if (!is_array($data) || empty($data['id'])) {
            throw new RuntimeException('野马PT用户基本信息响应格式异常');
        }
        return $data;
    }

    /**
     * 根据piecesHash批量反查野马PT种子ID；单次最多100个，超出时自动分批。
     * @param string[] $piecesHashes
     * @return array<string, int>
     */
    public function fetchTorrentIdsByPiecesHashes(array $piecesHashes): array
    {
        $normalized = [];
        foreach ($piecesHashes as $index => $piecesHash) {
            $piecesHash = strtolower(trim((string)$piecesHash));
            if (!preg_match('/^[a-f0-9]{40}$/', $piecesHash)) {
                throw new InvalidArgumentException("第{$index}个piecesHash不是40位SHA-1十六进制字符串");
            }
            $normalized[$piecesHash] = $piecesHash;
        }

        $result = [];
        foreach (array_chunk(array_values($normalized), 100) as $chunk) {
            $data = $this->postOpenApi(self::FETCH_ID_BY_PIECES_HASH_API, [
                'piecesHashList' => $chunk,
            ]);
            if (!is_array($data)) {
                throw new RuntimeException('野马PT piecesHash反查响应格式异常');
            }
            foreach ($data as $piecesHash => $torrentId) {
                if (is_numeric($torrentId)) {
                    $result[(string)$piecesHash] = (int)$torrentId;
                }
            }
        }

        return $result;
    }

    /**
     * 获取带临时下载凭证的种子链接
     * @throws TorrentException
     */
    public function downloadLink(Torrent $torrent): string
    {
        try {
            $url = $this->deferredDownloadLink($torrent->torrent_id);
            $torrent->setDownload($url, false);
            return $url;
        } catch (\Throwable $throwable) {
            throw new TorrentException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 下载指定种子；始终先生成临时凭证，忽略旧式Cookie下载链接
     */
    protected function doDownload(Curl $curl, Torrent $torrent): void
    {
        $curl->get($this->deferredDownloadLink($torrent->torrent_id));
    }

    /**
     * 爬虫真正需要下载种子时，才生成一次性下载链接。
     */
    protected function doDownloadMetadata(Curl $curl, SpiderTorrents $spiderTorrents): void
    {
        $spiderTorrents->download = $this->deferredDownloadLink((int)$spiderTorrents->id);
        $curl->get($spiderTorrents->download);
    }

    /**
     * 延迟生成下载链接：仅在IYUU查重通过、真正需要下载时申请临时凭证
     */
    public function deferredDownloadLink(int $torrentId): string
    {
        return $this->generateDownloadUrl($torrentId);
    }

    /**
     * 调用开放API生成下载凭证，并构造不含AuthKey的下载地址
     */
    private function generateDownloadUrl(int $torrentId): string
    {
        if ($torrentId <= 0) {
            throw new InvalidArgumentException('野马PT种子ID无效');
        }

        $downloadKey = $this->postOpenApi(self::GENERATE_DOWNLOAD_KEY_API . '?id=' . $torrentId);
        if (!is_string($downloadKey) || '' === $downloadKey) {
            throw new RuntimeException('生成野马PT种子下载凭证失败：接口未返回下载凭证');
        }

        $domain = rtrim($this->getConfig()->parseDomain(), '/');
        return $domain . '/' . self::DOWNLOAD_API . '?token=' . rawurlencode($downloadKey);
    }

    /**
     * 校验下载接口确实返回种子文件，并解析JSON业务错误
     */
    protected function afterDownload(Curl $curl, Torrent|SpiderTorrents $torrent): void
    {
        $contentType = strtolower((string)$curl->getResponseHeaders('Content-Type'));
        $mimeType = trim(explode(';', $contentType, 2)[0]);
        $result = json_decode(is_string($curl->response) ? $curl->response : '', true);
        if ('application/json' === $mimeType || is_array($result)) {
            $message = is_array($result) ? (string)($result['errorMessage'] ?? '下载接口返回JSON而不是种子文件') : '下载接口返回了无效JSON';
            $code = is_array($result) ? (int)($result['errorCode'] ?? 0) : 0;
            throw new RuntimeException('下载野马PT种子失败：' . $message, $code);
        }

        if ($curl->isSuccess() && 'application/x-bittorrent' !== $mimeType) {
            throw new RuntimeException('下载野马PT种子失败：响应Content-Type不是application/x-bittorrent');
        }

        parent::afterDownload($curl, $torrent);
    }

    /**
     * 提取种子ID的正则表达式
     * @return string
     */
    protected function getIdPatternInXML(): string
    {
        return '/torrent\/detail\/(\d+)\//i';
    }
}
