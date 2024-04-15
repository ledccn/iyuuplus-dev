<?php

namespace plugin\sms\api;

use Overtrue\EasySms\Exceptions\InvalidArgumentException;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;
use Overtrue\EasySms\Strategies\OrderStrategy;
use plugin\admin\app\model\Option;
use Overtrue\EasySms\EasySms;
use RuntimeException;
use support\exception\BusinessException;

/**
 * Sms
 */
class Sms
{

    /**
     * Option 表的name字段值
     */
    const OPTION_NAME = 'sms_setting';

    /**
     * 发送短信
     * @param string|array $to
     * @param array $message
     * @param array $gateways
     * @return array
     * @throws BusinessException
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     */
    public static function send($to, array $message, array $gateways = []): array
    {
        $sms = static::getSender();
        return $sms->send($to, $message, $gateways);
    }

    /**
     * 按照标签发送
     * @param $tagName
     * @param $to
     * @param array $data
     * @param array $gateways
     * @return array
     * @throws InvalidArgumentException
     * @throws NoGatewayAvailableException
     */
    public static function sendByTag($to, $tagName, array $data = [], array $gateways = []): array
    {
        $config = static::getConfig();
        $templates = [];
        foreach ($config['gateways'] as $gatewayName => $gateway) {
            if (!isset($gateway['tags'][$tagName])) {
                continue;
            }
            $tmp = $gateway['tags'][$tagName];
            $templates[$gatewayName] = ['template_id' => $tmp['template_id'], 'sign' => $tmp['sign']];
        }
        if (!$templates) {
            throw new RuntimeException("短信标签 $tagName 不存在");
        }
        $newConfig = [
            'timeout' => $config['timeout'],
            'default' => $config['default'],
            'gateways' => [],
        ];
        foreach ($templates as $gatewayName => $template) {
            $tmp = $config['gateways'][$gatewayName];
            unset($tmp['templates']);
            $tmp['sign_name'] = $template['sign'];
            $newConfig['gateways'][$gatewayName] = $tmp;
        }
        $sms = new EasySms($newConfig);
        return $sms->send($to, [
            // 不同的厂商有不同的模版id
            'template' => function ($gateway) use ($templates) {
                $gatewayName = $gateway->getName();
                return $templates[$gatewayName]['template_id'];
            },
            'data' => function($gateway) use ($data) {
                return $data;
            },
        ] , $gateways);
    }

    /**
     * Get Sms
     * @param array $config
     * @return EasySms
     * @throws BusinessException
     */
    public static function getSender(array $config = []): EasySms
    {
        if (!class_exists(EasySms::class)) {
            throw new BusinessException('请执行 composer require overtrue/easy-sms 并重启');
        }
        $config = $config ?: static::getConfig();
        if (!$config) {
            throw new BusinessException('未设置SMS配置');
        }
        return new EasySms($config);
    }

    /**
     * 获取配置
     * @return array
     */
    public static function getConfig(): array
    {
        $optionName = static::OPTION_NAME;
        $option = Option::where('name', $optionName)->value('value');
        $config = $option ? json_decode($option, true) : [];
        if (!$config) {
            $config = [
                'timeout' => 5.0,
                'default' => [
                    'strategy' => OrderStrategy::class,
                    'gateways' => [],
                ],
                'gateways' => [],
            ];
            $option = $option ?: new Option();
            $option->name = $optionName;
            $option->value = json_encode($config, JSON_UNESCAPED_UNICODE);
        }
        return $config;
    }

}