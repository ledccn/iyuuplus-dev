<?php

namespace Iyuu\BittorrentClient\Driver\transmission;

use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent;
use Iyuu\BittorrentClient\Exception\NotFoundException;
use Iyuu\BittorrentClient\Exception\ServerErrorException;
use Iyuu\BittorrentClient\Exception\UnauthorizedException;
use Ledc\Curl\Curl;

/**
 * transmission
 * @link https://github.com/transmission/transmission
 */
class Client extends Clients
{
    /**
     * 获取的种子字段
     */
    public const array TORRENT_GET_FIELDS = ["id", "name", "status", "hashString", "totalSize", "percentDone", "addedDate", "trackerStats", "leftUntilDone", "rateDownload", "rateUpload", "recheckProgress", "rateDownload", "rateUpload", "peersGettingFromUs", "peersSendingToUs", "uploadRatio", "uploadedEver", "downloadedEver", "downloadDir", "error", "errorString", "doneDate", "queuePosition", "activityDate"];
    /**
     * @var string
     */
    protected string $session_id = '';

    /**
     * @return void
     */
    protected function initialize(): void
    {
        $this->curl->setHeader('Referer', $this->getConfig()->getHostname() . '/transmission/web/');
        $this->curl->setXRequestedWith();
    }

    /**
     * 添加种子到下载器
     * @param Torrent $torrent
     * @return string|bool|null
     * @throws ServerErrorException
     * @throws UnauthorizedException
     */
    public function addTorrent(Torrent $torrent): string|bool|null
    {
        if ($torrent->isMetadata()) {
            return $this->addTorrentByMetadata($torrent->payload, $torrent->savePath, $torrent->parameters);
        }
        return $this->addTorrentByUrl($torrent->payload, $torrent->savePath, $torrent->parameters);
    }

    /**
     * 添加种子到下载器
     * @param string $metadata
     * @param string $savePath
     * @param array $extra
     * @return string|bool|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function addTorrentByMetadata(string $metadata = '', string $savePath = '', array $extra = []): string|bool|null
    {
        if (!empty($savePath)) {
            $extra['download-dir'] = $savePath;
        }
        $extra['metainfo'] = base64_encode($metadata);

        return $this->request("torrent-add", $extra);
    }

    /**
     * 添加种子到下载器
     * @param string $filename
     * @param string $savePath
     * @param array $extra
     * @return string|bool|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function addTorrentByUrl(string $filename = '', string $savePath = '', array $extra = []): string|bool|null
    {
        if (!empty($savePath)) {
            $extra['download-dir'] = $savePath;
        }
        $extra['filename'] = $filename;
        return $this->request("torrent-add", $extra);
    }

    /**
     * 获取全部种子列表
     * @param array $fields
     * @return array
     * @throws NotFoundException
     * @throws ServerErrorException
     * @throws UnauthorizedException
     */
    public function getList(array $fields = []): array
    {
        if (empty($fields)) {
            $fields = static::TORRENT_GET_FIELDS;
        }

        $request = ["fields" => $fields];
        $json = $this->request("torrent-get", $request);
        if (empty($json)) {
            throw new NotFoundException('从下载器获取种子列表失败，可能transmission暂时无响应，请稍后重试！' . PHP_EOL);
        }
        $res = json_decode($json, true);
        $result = $res['result'] ?? '';
        $torrents = $res['arguments']['torrents'] ?? [];
        if ('success' !== $result) {
            throw new NotFoundException("解析下载器返回结果时失败：" . $result . PHP_EOL);
        }
        if (empty($torrents)) {
            throw new NotFoundException("解析下载器种子数据为空" . PHP_EOL);
        }

        return $torrents;
    }

    /**
     * 获取做种列表
     * @return array
     * @throws UnauthorizedException|NotFoundException|ServerErrorException
     */
    public function getTorrentList(): array
    {
        $ids = [];
        $fields = array("id", "status", "name", "hashString", "downloadDir", "torrentFile");
        $res = $this->get($ids, $fields);
        $res = $res ? json_decode($res, true) : [];
        if (isset($res['result']) && $res['result'] === 'success') {
            // 成功
        } else {
            throw new NotFoundException("下载器无响应" . PHP_EOL);
        }
        if (empty($res['arguments']['torrents'])) {
            throw new NotFoundException("下载器种子数据为空" . PHP_EOL);
        }
        $res = $res['arguments']['torrents'];
        //file_put_contents(runtime_path('torrents.txt'), print_r($res, true));
        // 过滤，只保留正常做种
        $res = array_filter($res, function ($v) {
            return isset($v['status']) && $v['status'] === 6;
        });

        if (empty($res)) {
            throw new NotFoundException("从下载器未获取到做种数据" . PHP_EOL);
        }
        // 提取数组：hashString
        $info_hash = array_column($res, 'hashString');
        // 升序排序
        sort($info_hash);
        $json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
        // 去重 应该从文件读入，防止重复提交
        $sha1 = sha1($json);
        // 组装返回数据
        $hashArray = [];
        $hashArray['hash'] = $json;
        $hashArray['sha1'] = $sha1;
        // 变换数组：hashString为键名、目录为键值
        $hashArray['hashString'] = array_column($res, "downloadDir", 'hashString');
        // 转移做种使用
        $hashArray[static::TORRENT_LIST] = array_column($res, null, 'hashString');
        return $hashArray;
    }

    /**
     * @return string
     * @throws ServerErrorException
     */
    public function login(): string
    {
        $curl = $this->getCurl();
        $config = $this->getConfig();
        $curl->setBasicAuthentication($config->username ?? '', $config->password ?? '');
        $curl->get($config->getClientUrl());
        if ($curl->isSuccess() && ($response = $curl->response)) {
            if (preg_match("#<code>X-Transmission-Session-Id: (.*?)</code>#i", $response, $matches)) {
                $this->session_id = $matches[1] ?? '';
                return $this->session_id;
            }
        }

        if ($this->session_id = $this->fix409Conflict($curl)) {
            return $this->session_id;
        }

        if ($config->is_debug) {
            var_dump($curl);
        }
        throw new ServerErrorException('下载器登录失败：' . $curl->error_message);
    }

    /**
     * 修复 409 Conflict （2024年3月8日10:41:51）
     * @param Curl $curl
     * @return string
     */
    private function fix409Conflict(Curl $curl): string
    {
        if (409 === $curl->getHttpStatus()) {
            if ($sid = $curl->getResponseHeaders('X-Transmission-Session-Id')) {
                return $sid;
            }
        }
        return '';
    }

    /**
     * @return bool
     */
    public function logout(): bool
    {
        return true;
    }

    /**
     * 删除一个或多个种子
     * @param int|array $ids
     * @param bool $delete_local_data 是否删除数据
     * @return bool|string|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function delete(int|array $ids = [], bool $delete_local_data = false): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $request = array(
            'ids' => $ids,
            'delete-local-data' => $delete_local_data
        );
        return $this->request("torrent-remove", $request);
    }

    /**
     * 开始一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function start(int|array $ids = []): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-start', $params);
    }

    /**
     * @return mixed|string
     * @throws UnauthorizedException|ServerErrorException
     */
    public function status(): mixed
    {
        $response = $this->request('session-stats');
        if ($response) {
            $resp = json_decode($response, true);
            return $resp['result'] ?? 'error';
        }
        return 'null';
    }

    /**
     * 停止一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function stop(int|array $ids = []): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-stop', $params);
    }

    /**
     * 校验一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function verify(int|array $ids): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-verify', $params);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return string
     * @throws ServerErrorException
     * @throws UnauthorizedException
     */
    protected function request(string $method, array $arguments = []): string
    {
        $arguments = $this->cleanRequestData($arguments);
        $data = [
            'method' => $method,
            'arguments' => $arguments
        ];

        $retry = 1;
        do {
            if (!$this->session_id) {
                if (!$this->login()) {
                    throw new UnauthorizedException('无法获得 X-Transmission-Session-Id');
                }
            }
            $config = $this->getConfig();
            $curl = $this->initCurl();
            $curl->setBasicAuthentication($config->username ?? '', $config->password ?? '');
            $curl->setXRequestedWith();
            $header = array(
                'Referer' => $this->getConfig()->getHostname() . '/transmission/web/',
                'X-Transmission-Session-Id' => $this->session_id
            );
            foreach ($header as $key => $value) {
                $curl->setHeader($key, $value);
            }
            $curl->post($config->getClientUrl(), $data, true);
            if ($sid = $this->fix409Conflict($curl)) {
                $this->session_id = $sid;
            } else {
                $retry = 0;
            }
        } while (0 < $retry--);

        if ($curl->isSuccess()) {
            return $curl->response;
        }
        if ($config->is_debug) {
            var_dump($curl);
        }
        throw new ServerErrorException('下载器错误：' . $curl->error_message);
    }

    /**
     * 预处理请求报文
     * @param array $array
     * @return array|null
     */
    protected function cleanRequestData(array $array): ?array
    {
        if (0 === count($array)) {
            return null;
        }
        setlocale(LC_NUMERIC, 'en_US.utf8');    // Override the locale - if the system locale is wrong, then 12.34 will encode as 12,34 which is invalid JSON
        foreach ($array as $index => $value) {
            if (is_array($value)) {
                $array[$index] = $this->cleanRequestData($value);
            }    // Recursion
            if (empty($value) && ($value !== 0 || $value !== false)) {    // Remove empty members
                unset($array[$index]);
                continue; // Skip the rest of the tests - they may re-add the element.
            }

            if (is_numeric($value)) {
                // Force type-casting for proper JSON encoding (+0 is a cheap way to maintain int/float/etc)
                $array[$index] = $value + 0;
            } elseif (is_bool($value)) {
                // Store boolean values as 0 or 1
                $array[$index] = ($value ? 1 : 0);
            } elseif (is_string($value)) {
                $type = mb_detect_encoding($value, 'auto');
                if ($type !== 'UTF-8') {
                    $array[$index] = mb_convert_encoding($value, 'UTF-8');
                }
            }
        }
        return $array;
    }

    /**
     * 获取一个种子或所有种子的参数
     * Get information on torrents in transmission, if the ids parameter is
     * empty all torrents will be returned. The fields array can be used to return certain
     * fields. Default fields are: "id", "name", "status", "doneDate", "haveValid", "totalSize".
     *  key                         | type                        | source
     * ----------------------------+-----------------------------+---------
     * activityDate                | number                      | tr_stat
     * addedDate                   | number                      | tr_stat
     * bandwidthPriority           | number                      | tr_priority_t
     * comment                     | string                      | tr_info
     * corruptEver                 | number                      | tr_stat
     * creator                     | string                      | tr_info
     * dateCreated                 | number                      | tr_info
     * desiredAvailable            | number                      | tr_stat
     * doneDate                    | number                      | tr_stat
     * downloadDir                 | string                      | tr_torrent
     * downloadedEver              | number                      | tr_stat
     * downloadLimit               | number                      | tr_torrent
     * downloadLimited             | boolean                     | tr_torrent
     * error                       | number                      | tr_stat
     * errorString                 | string                      | tr_stat
     * eta                         | number                      | tr_stat
     * etaIdle                     | number                      | tr_stat
     * files                       | array (see below)           | n/a
     * fileStats                   | array (see below)           | n/a
     * hashString                  | string                      | tr_info
     * haveUnchecked               | number                      | tr_stat
     * haveValid                   | number                      | tr_stat
     * honorsSessionLimits         | boolean                     | tr_torrent
     * id                          | number                      | tr_torrent
     * isFinished                  | boolean                     | tr_stat
     * isPrivate                   | boolean                     | tr_torrent
     * isStalled                   | boolean                     | tr_stat
     * leftUntilDone               | number                      | tr_stat
     * magnetLink                  | string                      | n/a
     * manualAnnounceTime          | number                      | tr_stat
     * maxConnectedPeers           | number                      | tr_torrent
     * metadataPercentComplete     | double                      | tr_stat
     * name                        | string                      | tr_info
     * peer-limit                  | number                      | tr_torrent
     * peers                       | array (see below)           | n/a
     * peersConnected              | number                      | tr_stat
     * peersFrom                   | object (see below)          | n/a
     * peersGettingFromUs          | number                      | tr_stat
     * peersSendingToUs            | number                      | tr_stat
     * percentDone                 | double                      | tr_stat
     * pieces                      | string (see below)          | tr_torrent
     * pieceCount                  | number                      | tr_info
     * pieceSize                   | number                      | tr_info
     * priorities                  | array (see below)           | n/a
     * queuePosition               | number                      | tr_stat
     * rateDownload (B/s)          | number                      | tr_stat
     * rateUpload (B/s)            | number                      | tr_stat
     * recheckProgress             | double                      | tr_stat
     * secondsDownloading          | number                      | tr_stat
     * secondsSeeding              | number                      | tr_stat
     * seedIdleLimit               | number                      | tr_torrent
     * seedIdleMode                | number                      | tr_inactvelimit
     * seedRatioLimit              | double                      | tr_torrent
     * seedRatioMode               | number                      | tr_ratiolimit
     * sizeWhenDone                | number                      | tr_stat
     * startDate                   | number                      | tr_stat
     * status                      | number                      | tr_stat
     * trackers                    | array (see below)           | n/a
     * trackerStats                | array (see below)           | n/a
     * totalSize                   | number                      | tr_info
     * torrentFile                 | string                      | tr_info
     * uploadedEver                | number                      | tr_stat
     * uploadLimit                 | number                      | tr_torrent
     * uploadLimited               | boolean                     | tr_torrent
     * uploadRatio                 | double                      | tr_stat
     * wanted                      | array (see below)           | n/a
     * webseeds                    | array (see below)           | n/a
     * webseedsSendingToUs         | number                      | tr_stat
     * |                             |
     * -------------------+--------+-----------------------------+
     * files              | array of objects, each containing:   |
     * +-------------------------+------------+
     * | bytesCompleted          | number     | tr_torrent
     * | length                  | number     | tr_info
     * | name                    | string     | tr_info
     * -------------------+--------------------------------------+
     * fileStats          | a file's non-constant properties.    |
     * | array of tr_info.filecount objects,  |
     * | each containing:                     |
     * +-------------------------+------------+
     * | bytesCompleted          | number     | tr_torrent
     * | wanted                  | boolean    | tr_info
     * | priority                | number     | tr_info
     * -------------------+--------------------------------------+
     * peers              | array of objects, each containing:   |
     * +-------------------------+------------+
     * | address                 | string     | tr_peer_stat
     * | clientName              | string     | tr_peer_stat
     * | clientIsChoked          | boolean    | tr_peer_stat
     * | clientIsInterested      | boolean    | tr_peer_stat
     * | flagStr                 | string     | tr_peer_stat
     * | isDownloadingFrom       | boolean    | tr_peer_stat
     * | isEncrypted             | boolean    | tr_peer_stat
     * | isIncoming              | boolean    | tr_peer_stat
     * | isUploadingTo           | boolean    | tr_peer_stat
     * | isUTP                   | boolean    | tr_peer_stat
     * | peerIsChoked            | boolean    | tr_peer_stat
     * | peerIsInterested        | boolean    | tr_peer_stat
     * | port                    | number     | tr_peer_stat
     * | progress                | double     | tr_peer_stat
     * | rateToClient (B/s)      | number     | tr_peer_stat
     * | rateToPeer (B/s)        | number     | tr_peer_stat
     * -------------------+--------------------------------------+
     * peersFrom          | an object containing:                |
     * +-------------------------+------------+
     * | fromCache               | number     | tr_stat
     * | fromDht                 | number     | tr_stat
     * | fromIncoming            | number     | tr_stat
     * | fromLpd                 | number     | tr_stat
     * | fromLtep                | number     | tr_stat
     * | fromPex                 | number     | tr_stat
     * | fromTracker             | number     | tr_stat
     * -------------------+--------------------------------------+
     * pieces             | A bitfield holding pieceCount flags  | tr_torrent
     * | which are set to 'true' if we have   |
     * | the piece matching that position.    |
     * | JSON doesn't allow raw binary data,  |
     * | so this is a base64-encoded string.  |
     * -------------------+--------------------------------------+
     * priorities         | an array of tr_info.filecount        | tr_info
     * | numbers. each is the tr_priority_t   |
     * | mode for the corresponding file.     |
     * -------------------+--------------------------------------+
     * trackers           | array of objects, each containing:   |
     * +-------------------------+------------+
     * | announce                | string     | tr_tracker_info
     * | id                      | number     | tr_tracker_info
     * | scrape                  | string     | tr_tracker_info
     * | tier                    | number     | tr_tracker_info
     * -------------------+--------------------------------------+
     * trackerStats       | array of objects, each containing:   |
     * +-------------------------+------------+
     * | announce                | string     | tr_tracker_stat
     * | announceState           | number     | tr_tracker_stat
     * | downloadCount           | number     | tr_tracker_stat
     * | hasAnnounced            | boolean    | tr_tracker_stat
     * | hasScraped              | boolean    | tr_tracker_stat
     * | host                    | string     | tr_tracker_stat
     * | id                      | number     | tr_tracker_stat
     * | isBackup                | boolean    | tr_tracker_stat
     * | lastAnnouncePeerCount   | number     | tr_tracker_stat
     * | lastAnnounceResult      | string     | tr_tracker_stat
     * | lastAnnounceStartTime   | number     | tr_tracker_stat
     * | lastAnnounceSucceeded   | boolean    | tr_tracker_stat
     * | lastAnnounceTime        | number     | tr_tracker_stat
     * | lastAnnounceTimedOut    | boolean    | tr_tracker_stat
     * | lastScrapeResult        | string     | tr_tracker_stat
     * | lastScrapeStartTime     | number     | tr_tracker_stat
     * | lastScrapeSucceeded     | boolean    | tr_tracker_stat
     * | lastScrapeTime          | number     | tr_tracker_stat
     * | lastScrapeTimedOut      | boolean    | tr_tracker_stat
     * | leecherCount            | number     | tr_tracker_stat
     * | nextAnnounceTime        | number     | tr_tracker_stat
     * | nextScrapeTime          | number     | tr_tracker_stat
     * | scrape                  | string     | tr_tracker_stat
     * | scrapeState             | number     | tr_tracker_stat
     * | seederCount             | number     | tr_tracker_stat
     * | tier                    | number     | tr_tracker_stat
     * -------------------+-------------------------+------------+
     * wanted             | an array of tr_info.fileCount        | tr_info
     * | 'booleans' true if the corresponding |
     * | file is to be downloaded.            |
     * -------------------+--------------------------------------+
     * webseeds           | an array of strings:                 |
     * +-------------------------+------------+
     * | webseed                 | string     | tr_info
     * +-------------------------+------------+
     *
     * @param array fields An array of return fields
     * @param int|array ids A list of transmission torrent ids
     *  示例 Example:
     * Say we want to get the name and total size of torrents #7 and #10.
     *
     * 请求 Request:
     * {
     * "arguments": {
     * "fields": [ "id", "name", "totalSize" ],
     * "ids": [ 7, 10 ]
     * },
     * "method": "torrent-get",
     * "tag": 39693
     * }
     *
     * 响应 Response:
     * {
     * "arguments": {
     * "torrents": [
     * {
     * "id": 10,
     * "name": "Fedora x86_64 DVD",
     * "totalSize": 34983493932,
     * },
     * {
     * "id": 7,
     * "name": "Ubuntu x86_64 DVD",
     * "totalSize", 9923890123,
     * }
     * ]
     * },
     * "result": "success",
     * "tag": 39693
     * }
     *
     * @return bool|string|null
     * @throws UnauthorizedException|ServerErrorException
     */
    public function get($ids = [], $fields = []): bool|string|null
    {
        $default = ["id", "name", "status", "doneDate", "haveValid", "totalSize", 'labels', 'peers', 'group'];
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (empty($fields)) {
            $fields = $default;
        } else {
            $fields = is_array($fields) ? array_merge($default, $fields) : $default;
        }
        $request = array(
            "fields" => $fields,
            "ids" => $ids
        );
        return $this->request("torrent-get", $request);
    }

    /**
     * 抽象方法，子类实现
     * 解析结果
     * @param mixed $result
     * @return array
     */
    public function response(mixed $result): array
    {
        $rs = [
            'result' => 'success',      //success or fail
            'data' => [],
        ];

        if (isset($result['result']) && $result['result'] == 'success') {
            $_key = isset($result['arguments']['torrent-added']) ? 'torrent-added' : 'torrent-duplicate';
            $id = $result['arguments'][$_key]['id'];
            $name = $result['arguments'][$_key]['name'];
            echo "名字：" . $name . PHP_EOL;
            echo "********RPC添加下载任务成功 [" . $result['result'] . "] (id=" . $id . ")" . PHP_EOL . PHP_EOL;
            $rs['data'] = [
                'id' => $id,
                'name' => $name
            ];
        } else {
            $rs['result'] = empty($result['result']) ? '未知错误，请稍后重试！' : $result['result'];
            echo "-----RPC添加种子任务，失败 [" . $rs['result'] . "]" . PHP_EOL . PHP_EOL;
        }

        return $rs;
    }
}
