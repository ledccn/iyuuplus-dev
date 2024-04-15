<?php

namespace Iyuu\BittorrentClient\Contracts;

use JsonSerializable;

/**
 * 待添加的种子结构
 */
class Torrent implements JsonSerializable
{
    /**
     * 保存路径
     * @var string
     */
    public string $savePath = '';

    /**
     * 表单字段名
     * @var string
     */
    public string $name = '';

    /**
     * 文件名
     * @var string
     */
    public string $filename = '';

    /**
     * 其他参数
     * @var array
     */
    public array $parameters = [];

    /**
     * 构造函数
     * @param string $payload 有效载荷，数据类型依据metadata确定：true二进制、false下载种子的URL链接
     * @param bool $metadata 是否二进制种子文件
     */
    public function __construct(public readonly string $payload, public readonly bool $metadata = true)
    {
    }

    /**
     * 是否二进制种子文件
     * @return bool
     */
    public function isMetadata(): bool
    {
        return $this->metadata;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
