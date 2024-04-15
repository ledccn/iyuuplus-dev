<?php

namespace Iyuu\SiteManager\Contracts;

use InvalidArgumentException;

/**
 * 获取站点配置
 */
class Config implements ConfigInterface
{
    /**
     * 获取配置
     * @param string $name
     * @return array
     */
    public function get(string $name): array
    {
        $filename = self::getFilename();
        clearstatcache();
        if (!is_file($filename)) {
            throw new InvalidArgumentException('配置文件不存在：' . $filename);
        }

        $items = json_decode(file_get_contents($filename), true);
        $sites = array_column($items, null, 'site');
        if (empty($sites[$name])) {
            throw new InvalidArgumentException('不支持的站点：' . $name);
        }

        $config = $sites[$name];
        if ($config['disabled'] ?? true) {
            throw new InvalidArgumentException('请启用站点：' . $name);
        }
        return $config;
    }

    /**
     * 获取配置文件的完整路径
     * @return string
     */
    public static function getFilename(): string
    {
        return rtrim(runtime_path(), '/\\') . '/backup/cn_sites.json';
    }
}
