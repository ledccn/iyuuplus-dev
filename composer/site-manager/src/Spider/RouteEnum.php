<?php

namespace Iyuu\SiteManager\Spider;

use InvalidArgumentException;

/**
 * 路由枚举
 */
enum RouteEnum: string
{
    /**
     * 种子列表页
     */
    case N1 = 'torrents.php?incldead=0&page={page}';
    /**
     * 种子RSS页面
     */
    case N2 = 'torrentrss.php?rows=50&linktype=dl&passkey={passkey}';
    /**
     * 种子列表页【Uint3D架构】
     */
    case N3 = 'torrents?page={page}';
    /**
     * 种子RSS页面【Uint3D架构】莫妮卡
     */
    case N4 = 'rss/13.{rsskey}';
    /**
     * 种子列表页：聆音阅听专区
     */
    case N5 = 'special.php?incldead=0&page={page}';
    /**
     * 种子RSS页面：观众站
     */
    case N6 = 'torrentrss.php?rows=50&torrent_type=1&linktype=dl&rsskey={rsskey}';
    /**
     * 种子RSS页面：高清城市
     */
    case N7 = 'trss?rows=50&linktype=dl&passkey={passkey}';
    /**
     * 种子列表页：TTG
     */
    case N8 = 'browse.php?c=M&page={page}';
    /**
     * 种子RSS页面【Uint3D架构】普斯特
     */
    case N9 = 'rss/33.{rsskey}';
    /**
     * 种子RSS页面【朱雀】
     */
    case N10 = 'api/torrent/rss/{rss_key}/{torrent_key}';
    /**
     * 种子列表页【朱雀】
     * @deprecated
     */
    case N11 = 'api/torrent/list?page={page}&size=20';
    /**
     * 种子列表页【朱雀】
     */
    case N12 = 'api/torrent/advancedSearch?page={page}&size=20';
    /**
     * 种子列表页：海棠曲艺园 有声读物
     */
    case N13 = 'live.php?incldead=0&page={page}';

    /**
     * 检查枚举名字
     * @param string $name
     * @return bool
     */
    public static function hasName(string $name): bool
    {
        return in_array(strtoupper($name), array_column(self::cases(), 'name'));
    }

    /**
     * 获取枚举值
     * @param string $name
     * @return string
     */
    public static function getValue(string $name): string
    {
        $name = strtoupper($name);
        $list = self::toArray();
        if (!array_key_exists($name, $list)) {
            throw new InvalidArgumentException('路由不存在');
        }

        return $list[$name];
    }

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
