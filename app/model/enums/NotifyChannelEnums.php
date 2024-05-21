<?php

namespace app\model\enums;

use plugin\admin\app\model\Option;

/**
 * 通知渠道配置枚举类
 */
enum NotifyChannelEnums: string
{
    /**
     * 通知渠道，iyuu
     */
    case notify_iyuu = 'notify_iyuu';

    /**
     * 通知渠道，Server酱
     */
    case notify_server_chan = 'notify_server_chan';

    /**
     * 通知渠道，Bark
     */
    case notify_bark = 'notify_bark';

    /**
     * 通知渠道，E-Mail
     */
    case notify_email = 'notify_email';

    /**
     * 通知渠道，企业微信机器人
     */
    case notify_qy_weixin = 'notify_qy_weixin';

    /**
     * 通知渠道，群晖机器人Synology Chat
     */
    case notify_synology_chat = 'notify_synology_chat';

    /**
     * 通知渠道，自定义通知
     */
    case notify_webhook = 'notify_webhook';

    /**
     * 配置项前缀
     */
    private const string PREFIX = 'sys_config_';

    /**
     * 获取配置项名称
     * @param NotifyChannelEnums $enum
     * @return string
     */
    private static function getConfigName(self $enum): string
    {
        return self::PREFIX . $enum->value;
    }

    /**
     * 获取配置
     * @param NotifyChannelEnums $enum
     * @return array
     */
    public static function getConfig(self $enum): array
    {
        $name = self::getConfigName($enum);
        $config = Option::where('name', $name)->value('value');
        if (empty($config)) {
            return [];
        }

        $config = json_decode($config, true);
        return $config ?: [];
    }

    /**
     * 保存配置
     * @param NotifyChannelEnums $enum
     * @param array $data
     * @return void
     */
    public static function saveConfig(self $enum, array $data): void
    {
        $name = self::getConfigName($enum);
        Option::updateOrInsert(['name' => $name], ['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
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
