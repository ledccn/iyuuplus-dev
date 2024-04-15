<?php

namespace Iyuu\BittorrentClient;

/**
 * 客户端枚举值
 */
enum ClientEnums: string
{
    /**
     * qBittorrent
     */
    case qBittorrent = 'qBittorrent';

    /**
     * Transmission
     */
    case transmission = 'transmission';

    /**
     * 枚举条目转为数组
     * - 名 => 值
     * @return array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }
}
