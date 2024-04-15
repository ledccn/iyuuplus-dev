<?php

namespace plugin\sms\app\admin\model;

use plugin\admin\app\model\Option;
use plugin\sms\api\Sms;

/**
 * 短信标签相关
 */
class Tag
{
    /**
     * 获取标签
     * @param $gateway
     * @param $name
     * @return mixed|null
     */
    public static function get($gateway, $name)
    {
        $config = Sms::getConfig();
        return $config['gateways'][$gateway]['tags'][$name] ?? null;
    }

    /**
     * 保存标签
     * @param $gateway
     * @param $name
     * @param $value
     * @return void
     */
    public static function save($gateway, $name, $value)
    {
        $config = Sms::getConfig();
        $config['gateways'][$gateway]['tags'][$name] = $value;
        $optionName = Sms::OPTION_NAME;
        if (!$option = Option::where('name', $optionName)->first()) {
            $option = new Option;
        }
        $option->name = $optionName;
        $option->value = json_encode($config, JSON_UNESCAPED_UNICODE);
        $option->save();
    }

    /**
     * 删除标签
     * @param $gateway
     * @param array $names
     * @return void
     */
    public static function delete($gateway, array $names)
    {
        $config = Sms::getConfig();
        foreach ($names as $name) {
            unset($config['gateways'][$gateway]['tags'][$name]);
        }
        $optionName = Sms::OPTION_NAME;
        if (!$option = Option::where('name', $optionName)->first()) {
            $option = new Option;
        }
        $option->name = $optionName;
        $option->value = json_encode($config, JSON_UNESCAPED_UNICODE);
        $option->save();
    }
    
}
