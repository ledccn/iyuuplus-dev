<?php

namespace app\admin\services\download;

use plugin\admin\app\model\Option;
use RuntimeException;

/**
 * 工具类
 */
class PacificUtil
{
    /**
     * 获取配置
     * @return array
     */
    public static function getConfig(): array
    {
        $config = Option::where('name', 'system_config')->value('value');
        if (empty($config)) {
            throw new RuntimeException('未配置基本设置：通用设置->基本设置');
        }
        $config = json_decode($config, true);
        if (empty($config['logo'])) {
            throw new RuntimeException('未配置基本信息：通用设置->基本设置->基本信息');
        }

        $services_token = $config['logo']['services_token'] ?? '';
        if (empty($services_token)) {
            throw new RuntimeException('未配置服务器地址或用户Token');
        }
        return [$services_token];
    }
}