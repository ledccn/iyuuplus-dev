<?php

namespace Iyuu\SiteManager\Contracts;

use InvalidArgumentException;
use JsonSerializable;
use SplFileObject;

/**
 * 下载响应
 */
readonly class Response implements JsonSerializable
{
    /**
     * 构造函数
     * @param string $payload 有效载荷，数据类型依据metadata确定：true二进制、false下载种子的URL链接
     * @param bool $metadata 是否二进制种子文件
     */
    public function __construct(public string $payload, public bool $metadata)
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
     * 保存到文件
     * @param string $filename
     * @return SplFileObject|null
     */
    public function output(string $filename): ?SplFileObject
    {
        if ($this->metadata) {
            clearstatcache();
            set_error_handler(function ($type, $msg) use (&$error) {
                $error = $msg;
            });
            $path = pathinfo($filename, PATHINFO_DIRNAME);
            if (!is_dir($path) && !mkdir($path, 0777, true)) {
                restore_error_handler();
                throw new InvalidArgumentException(sprintf('Unable to create the "%s" directory (%s)', $path, strip_tags($error)));
            }
            restore_error_handler();
            if (false !== file_put_contents($filename, $this->payload)) {
                return new SplFileObject($filename, 'r');
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
