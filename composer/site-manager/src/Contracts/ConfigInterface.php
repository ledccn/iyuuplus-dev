<?php

namespace Iyuu\SiteManager\Contracts;

/**
 * 获取站点配置接口
 */
interface ConfigInterface
{
    /**
     * @param string $name 站点名字
     * @return array
     */
    public function get(string $name): array;
}
