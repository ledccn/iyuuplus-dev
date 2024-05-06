<?php

namespace app\admin\support;

/**
 * webman后台通知
 * @method static success(string $msg) 成功消息
 * @method static error(string $msg) 错误消息
 * @method static warning(string $msg) 警告消息
 * @method static info(string $msg) 通用消息
 */
class NotifyAdmin
{
    /**
     * 频道名称
     */
    const string CHANNEL_NAME = 'private-webman-admin';

    /**
     * @param string $name
     * @param array $arguments
     * @return bool|string
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $data = [
            'type' => $name,
            'msg' => $arguments[0],
        ];
        unset($arguments[0]);
        return PushApi::trigger(self::CHANNEL_NAME, 'notify', $data, ...$arguments);
    }

    /**
     * 命令行输出
     * @param string $name
     * @param string $msg
     * @return bool
     */
    public static function shellOutput(string $name, string $msg): bool
    {
        $data = [
            'type' => $name,
            'msg' => $msg
        ];
        return PushApi::trigger(self::CHANNEL_NAME, 'shell_output', $data);
    }

    /**
     * 进度条
     * @param string $type
     * @param int $success 成功数
     * @param int $fail 失败数
     * @param int $total 总共条数
     * @param array $args 其他参数
     * @return void
     */
    public static function progress(string $type, int $success, int $fail, int $total, array $args = []): void
    {
        $data = [
            'type' => $type,
            'success' => $success,
            'fail' => $fail,
            'total' => $total,
            'args' => $args
        ];
        PushApi::trigger(self::CHANNEL_NAME, 'progress', $data);
    }
}
