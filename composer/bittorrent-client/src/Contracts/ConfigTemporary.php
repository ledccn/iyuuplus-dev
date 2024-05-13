<?php

namespace Iyuu\BittorrentClient\Contracts;

/**
 * 临时单一配置
 */
readonly class ConfigTemporary implements ConfigInterface
{
    /**
     * 构造函数
     * @param array $data
     */
    public function __construct(public array $data)
    {
    }

    /**
     * @param string $name
     * @return array
     */
    public function get(string $name): array
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @return array|string[]
     */
    public function decode(string $name): array
    {
        return [0, $this->data['brand']];
    }

    /**
     * @param int $uniqid
     * @param string $brand
     * @return string
     */
    public function encode(int $uniqid, string $brand): string
    {
        return implode(':', [0, $this->data['brand']]);
    }
}
