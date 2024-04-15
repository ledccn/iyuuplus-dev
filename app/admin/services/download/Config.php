<?php

namespace app\admin\services\download;

use app\model\Site;
use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\ConfigInterface;

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
        if ($config = Site::uniqueSite($name)) {
            if ($config->disabled) {
                throw new InvalidArgumentException('请启用站点：' . $name);
            }
            return $config->toArray();
        }
        throw new InvalidArgumentException('不支持的站点：' . $name);
    }
}
