<?php

namespace app\admin\support;

use Webman\Push\Api;
use Webman\Push\PushException;

/**
 * PushApi调用类
 * @method static string|bool trigger(string $channels, string $event, mixed $data, string $socket_id = null)
 * @mixin Api
 */
class PushApi
{
    /**
     * @var Api|null
     */
    protected static ?Api $api = null;

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws PushException
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::connection()->{$name}(... $arguments);
    }

    /**
     * @return Api
     * @throws PushException
     */
    protected static function connection(): Api
    {
        if (null === static::$api) {
            static::$api = new Api(
            // webman下可以直接使用config获取配置，非webman环境需要手动写入相应配置
                'http://127.0.0.1:' . parse_url(config('plugin.webman.push.app.api'), PHP_URL_PORT),
                config('plugin.webman.push.app.app_key'),
                config('plugin.webman.push.app.app_secret')
            );
        }
        return static::$api;
    }
}