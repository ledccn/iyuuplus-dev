<?php

namespace app\admin\services\transfer;

/**
 * 运载火箭
 */
class TransferRocket
{
    /**
     * 种子文件的完整路径
     * @var string
     */
    public string $torrentFile = '';

    /**
     * 可删除种子的有效参数
     * @var mixed|null
     */
    public mixed $torrentDelete = null;

    /**
     * @param string $infohash 种子INFOHASH
     * @param string $path 用户配置的种子目录
     * @param array $move 下载器内全部做种信息
     */
    public function __construct(
        public readonly string $infohash,
        public readonly string $path,
        public readonly array  $move,
    )
    {
    }
}
